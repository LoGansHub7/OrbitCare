(function (root) {
    'use strict';

    const stopWords = new Set(['a', 'an', 'and', 'are', 'can', 'could', 'do', 'does', 'for', 'how', 'i', 'is', 'it', 'me', 'my', 'of', 'please', 'the', 'to', 'what', 'whats', 'with', 'you']);
    const metrics = [
        { id: 'resting_heart_rate', label: 'resting heart rate', aliases: ['resting heart rate', 'heart rate', 'pulse', 'heart beat'] },
        { id: 'blood_pressure', label: 'blood pressure', aliases: ['blood pressure', 'bp'] },
        { id: 'spo2', label: 'oxygen saturation', aliases: ['spo2', 'oxygen saturation', 'blood oxygen'] },
        { id: 'sleep_duration', label: 'sleep duration', aliases: ['sleep duration', 'sleep hours', 'hours of sleep', 'sleep'] },
        { id: 'body_temperature', label: 'body temperature', aliases: ['body temperature', 'temperature', 'temp'] },
        { id: 'radiation_dose_sim', label: 'radiation dose', aliases: ['radiation dose', 'radiation exposure', 'dose'] },
        { id: 'hydration', label: 'hydration', aliases: ['hydration', 'water intake', 'hydrated'] },
        { id: 'body_mass', label: 'body mass', aliases: ['body mass', 'body weight', 'weight'] },
        { id: 'fatigue_rating', label: 'fatigue rating', aliases: ['fatigue', 'fatigue rating', 'tiredness'] },
        { id: 'exercise_duration', label: 'exercise duration', aliases: ['exercise duration', 'exercise minutes'] },
    ];
    const bodies = ['head', 'chest', 'back', 'abdomen', 'stomach', 'arm', 'leg', 'eye', 'heart', 'lungs'];
    const symptoms = ['pain', 'headache', 'dizzy', 'dizziness', 'nausea', 'fatigue', 'breathlessness', 'vomiting'];
    const timePatterns = [
        ['last_week', /\b(?:last|previous) week\b/],
        ['last_month', /\b(?:last|previous) month\b/],
        ['yesterday', /\byesterday\b/],
        ['today', /\btoday\b/],
        ['latest', /\b(?:latest|recent|current|now)\b/],
    ];
    // Red-flag check runs before any other routing; a trigger matches only as whole words.
    function findProcedure(normalized, procedures = []) {
        const padded = ` ${normalized} `;
        return procedures.find((procedure) => (procedure.triggers || []).some((trigger) =>
            padded.includes(` ${normalize(trigger)} `))) || null;
    }

    function normalize(text) {
        return text.toLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, ' ').trim().replace(/\s+/g, ' ');
    }

    function stem(word) {
        if (word.length > 5 && word.endsWith('ing')) return word.slice(0, -3);
        if (word.length > 4 && word.endsWith('ed')) return word.slice(0, -2);
        if (word.length > 4 && word.endsWith('s')) return word.slice(0, -1);
        return word;
    }

    function tokens(text) {
        return normalize(text).split(' ').filter((word) => word && !stopWords.has(word)).map(stem);
    }

    function distance(a, b) {
        if (Math.abs(a.length - b.length) > 2) return 3;
        let previous = Array.from({ length: b.length + 1 }, (_, index) => index);
        for (let i = 1; i <= a.length; i++) {
            const current = [i];
            for (let j = 1; j <= b.length; j++) {
                current[j] = Math.min(current[j - 1] + 1, previous[j] + 1, previous[j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1));
            }
            previous = current;
        }
        return previous[b.length];
    }

    function phraseScore(text, phrase) {
        // An exact phrase match wins even when every word is a stop-word ("what can you do").
        const wholePhrase = normalize(phrase);
        if (wholePhrase && ` ${normalize(text)} `.includes(` ${wholePhrase} `)) return 1;
        const query = tokens(text);
        const wanted = tokens(phrase);
        if (!wanted.length || !query.length) return 0;
        let matches = 0;
        const used = new Set();
        for (const word of wanted) {
            let best = 0;
            let bestIndex = -1;
            query.forEach((candidate, index) => {
                if (used.has(index)) return;
                const tolerance = word.length >= 5 ? 1 : 0;
                const d = distance(word, candidate);
                const score = d === 0 ? 1 : d <= tolerance ? 0.75 : 0;
                if (score > best) { best = score; bestIndex = index; }
            });
            if (bestIndex >= 0) used.add(bestIndex);
            matches += best;
        }
        return matches / wanted.length;
    }

    function extract(text) {
        const normalized = normalize(text);
        const matched = metrics.map((metric) => ({
            ...metric,
            score: Math.max(...metric.aliases.map((alias) => phraseScore(text, alias))),
            specificity: Math.max(...metric.aliases.filter((alias) => phraseScore(text, alias) >= 0.75).map((alias) => tokens(alias).length), 0),
        })).filter((metric) => metric.score >= 0.75)
            .sort((a, b) => b.specificity - a.specificity || b.score - a.score);
        return {
            metric: matched[0]?.id || null,
            metrics: matched.map((metric) => metric.id),
            body_parts: bodies.filter((body) => normalized.split(' ').includes(body)),
            symptoms: symptoms.filter((symptom) => normalized.split(' ').includes(symptom)),
            time: timePatterns.find(([, pattern]) => pattern.test(normalized))?.[0] || null,
        };
    }

    function select(text, knowledge, context = {}) {
        const normalized = normalize(text);
        const entities = extract(text);
        if (isBasicArithmetic(text)) return { intent: 'arithmetic', confidence: 1, entities };
        const chat = basicConversation(normalized);
        if (chat) return { intent: 'smalltalk', response: chat, confidence: 1, entities };
        const procedure = findProcedure(normalized, knowledge.procedures);
        if (procedure) return { intent: 'emergency', procedure: procedure.id, confidence: 1, entities };
        if (/\b(?:alerts?|warnings?|flags?)\b/.test(normalized)) return { intent: 'alerts', confidence: 1, entities };
        if (entities.metrics.length > 1) return { intent: 'clarify', confidence: 0, entities };
        // "and" is left out on purpose: it appears in ordinary topic questions ("bones and muscles").
        const followUp = /\b(?:what about|how about|why|last week|yesterday)\b/.test(normalized);
        if (!entities.metric && followUp && context.metric) entities.metric = context.metric;
        // Generic advice ("how do I sleep better") goes to topics unless the question asks about the user's own data.
        const advice = /\b(?:how (?:do|can|should|would|to)|should i|tips?|better|reduce|prevent|protect|manage|cope|improve)\b/.test(normalized);
        const ownership = /\b(?:latest|current|today|yesterday|last|this week)\b/.test(normalized);
        const generic = advice && !ownership && !followUp;
        const personal = /\b(?:my|mine|latest|last|current|yesterday|today|week|high|low|why|what about|how about|did i)\b/.test(normalized);
        if (entities.metric && !generic && (personal || followUp || !['sleep_duration', 'hydration', 'body_mass'].includes(entities.metric))) {
            return { intent: 'metric', confidence: 0.9, entities };
        }

        const candidates = [...(knowledge.intents || []), ...(knowledge.topics || [])];
        const scores = candidates.map((candidate) => {
            // Keep the best-matching phrase; on equal scores prefer the more specific (longer) phrase.
            let best = { confidence: 0, specificity: 0 };
            for (const phrase of candidate.phrases) {
                const confidence = phraseScore(text, phrase);
                const specificity = tokens(phrase).length;
                if (confidence > best.confidence || (confidence === best.confidence && specificity > best.specificity)) {
                    best = { confidence, specificity };
                }
            }
            return { intent: candidate.id, category: candidate.category, ...best };
        }).sort((a, b) => b.confidence - a.confidence || b.specificity - a.specificity);
        const tied = scores[1] && scores[0].confidence - scores[1].confidence < 0.1;
        // Ties are resolved when both topics sit in one category or the leader's phrase is more specific.
        const resolved = scores[1] && ((scores[0].category && scores[0].category === scores[1].category) || scores[0].specificity > scores[1].specificity);
        if (!scores.length || scores[0].confidence < 0.75 || (tied && !resolved)) {
            return { intent: 'clarify', confidence: scores[0]?.confidence || 0, entities };
        }
        const { category, specificity, ...winner } = scores[0];
        return { ...winner, entities };
    }

    function basicConversation(text) {
        if (/^(hi|hello|hey|good morning|good afternoon|good evening)$/.test(text)) return 'Hello! What can I help you with? I can answer general questions, do simple calculations, and report recorded crew data.';
        if (/^(how are you|how are you doing|whats up)$/.test(text)) return 'I’m here and ready to help. I can answer general questions, do simple calculations, or look up recorded crew data.';
        if (/^(who are you|what are you|what is orbit|what is orbitcare)$/.test(text)) return 'I’m ORBIT, the OrbitCare assistant. I can answer general spaceflight health questions from my local reference library, report available recorded data, and help with simple tasks. I cannot diagnose or replace the mission medical team.';
        if (/^(thank you|thanks|thank you very much)$/.test(text)) return 'You’re welcome!';
        if (/^(bye|goodbye|see you)$/.test(text)) return 'Goodbye. I’ll be here if you need help.';
        if (/^(what can you do|help|what do you do)$/.test(text)) return 'I can answer general spaceflight health questions, report recorded crew data when available, and handle simple calculations and conversation. What would you like to try?';
        return null;
    }

    function isBasicArithmetic(text) {
        return /^(?:(?:what is|what's|calculate)\s+)?\d+(?:\.\d+)?\s*(?:\+|plus|add|minus|subtract|\-|\*|×|times|x|\/|÷|divided by)\s*\d+(?:\.\d+)?\??$/i.test(String(text).trim());
    }

    function solveBasicArithmetic(text) {
        const match = String(text).trim().match(/^(?:(?:what is|what's|calculate)\s+)?(\d+(?:\.\d+)?)\s*(\+|plus|add|minus|subtract|\-|\*|×|times|x|\/|÷|divided by)\s*(\d+(?:\.\d+)?)\??$/i);
        if (!match) return null;
        const a = Number(match[1]);
        const b = Number(match[3]);
        const op = match[2].toLowerCase();
        const result = ['+', 'plus', 'add'].includes(op) ? a + b
            : ['-', 'minus', 'subtract'].includes(op) ? a - b
                : ['*', '×', 'times', 'x'].includes(op) ? a * b
                    : b === 0 ? null : a / b;
        return result === null ? 'Division by zero is undefined.' : `${a} ${op} ${b} = ${Number(result.toPrecision(10))}`;
    }

    function metricResponse(metricId, snapshot, time, text, now) {
        const metric = metrics.find((item) => item.id === metricId);
        if (!metric) return 'I cannot identify that metric. Please choose another topic.';
        if (time && !['latest', 'today'].includes(time)) return `I only have the latest cached snapshot, not ${time.replace('_', ' ')} history for ${metric.label}. Check your records or ask the mission medical team.`;
        if (/\b(?:why|high|low)\b/.test(normalize(text))) return `I cannot determine why ${metric.label} may have changed or whether it is high or low. Review the data with your mission medical team.`;
        if (!snapshot) return 'No personal snapshot is available on this device. Connect while signed in to refresh it.';
        if (metricId === 'radiation_dose_sim') {
            const radiation = snapshot.radiation;
            if (!radiation || !radiation.recorded_at) return 'No radiation dose value is available in your cached snapshot. Connect while signed in to refresh your recorded data.';
            const recorded = new Date(radiation.recorded_at);
            if (Number.isNaN(recorded.getTime())) return 'The timestamp for your cached radiation reading is unavailable. Check your records.';
            const stale = now.getTime() - recorded.getTime() > 24 * 3600000;
            const simulated = radiation.is_simulated ? ' This is simulated demonstration data.' : '';
            return `Your latest recorded radiation dose rate is ${radiation.dose_rate} mSv/day; cumulative dose is ${radiation.cumulative_dose} mSv, recorded ${recorded.toISOString()}.${stale ? ' This record is more than 24 hours old.' : ''}${simulated} This is recorded data only, not a safety assessment. Follow mission radiation procedures and ask the medical team to interpret it.`;
        }
        if (metricId === 'blood_pressure') {
            const parts = ['blood_pressure_systolic', 'blood_pressure_diastolic'].map((code) =>
                (snapshot.readings || []).find((item) => item.code === code));
            const available = parts.filter(Boolean);
            if (!available.length) return 'No blood pressure value is available in your cached snapshot.';
            const descriptions = available.map((item) => {
                const observed = new Date(item.observed_at);
                if (Number.isNaN(observed.getTime())) return `${item.label}: timestamp unavailable`;
                if (time === 'today' && observed.toISOString().slice(0, 10) !== now.toISOString().slice(0, 10)) return `${item.label}: no value from today`;
                const stale = Number.isFinite(item.freshness_hours) && now.getTime() - observed.getTime() > item.freshness_hours * 3600000;
                return `${item.label}: ${item.value} ${item.unit}, recorded ${observed.toISOString()} (source: ${item.source || 'unknown'})${stale ? ', stale' : ''}`;
            });
            if (available.length < 2) descriptions.push('The other component is unavailable.');
            return `Your latest recorded blood pressure components: ${descriptions.join('; ')}. These are separate readings and may not form a single measurement. This is not a diagnosis or medical clearance.`;
        }
        const reading = (snapshot.readings || []).find((item) => item.code === metricId);
        const checkinMap = { sleep_duration: ['sleep_hours', 'hours'], fatigue_rating: ['fatigue_rating', '/ 5'], exercise_duration: ['exercise_minutes', 'min'] };
        let value = reading?.value;
        let unit = reading?.unit;
        let observedAt = reading?.observed_at;
        let source = reading?.source;
        let freshnessHours = reading?.freshness_hours;
        if (checkinMap[metricId]) {
            const [key, checkinUnit] = checkinMap[metricId];
            value = snapshot.checkin?.[key];
            unit = checkinUnit;
            observedAt = snapshot.checkin?.submitted_at;
            source = snapshot.checkin?.source;
            freshnessHours = snapshot.checkin?.freshness_hours?.[metricId];
        }
        if (value === undefined || value === null || !observedAt) return `No ${metric.label} value is available in your cached snapshot.`;
        const observed = new Date(observedAt);
        if (Number.isNaN(observed.getTime())) return `The timestamp for your cached ${metric.label} is unavailable. Check your records.`;
        if (time === 'today' && observed.toISOString().slice(0, 10) !== now.toISOString().slice(0, 10)) return `There is no ${metric.label} value from today in your cached snapshot.`;
        const stale = Number.isFinite(freshnessHours) && now.getTime() - observed.getTime() > freshnessHours * 3600000;
        const ruleCode = { resting_heart_rate: 'resting_heart_rate_change', sleep_duration: 'sleep_fatigue_review', fatigue_rating: 'sleep_fatigue_review' }[metricId];
        const ruleState = snapshot.rule_states?.[ruleCode];
        const flag = ruleState === 'triggered' ? ' The experimental trend rule requests review.' : ruleState === 'insufficient_data' ? ' The experimental trend rule has insufficient data.' : '';
        return `Your latest recorded ${metric.label}: ${value} ${unit || ''}, recorded ${observed.toISOString()} (source: ${source || 'unknown'}).${stale ? ' This record is stale.' : ''}${flag} This is not a diagnosis or medical clearance.`;
    }

    function numbered(steps = []) {
        return steps.map((step, index) => `${index + 1}. ${step}`).join('\n');
    }

    function emergencyReply(procedure, disclaimer) {
        return [
            `EMERGENCY: ${procedure.title}. Contact your mission medical team immediately.`,
            numbered(procedure.steps),
            `Escalate: ${procedure.escalate}`,
            `Source: ${procedure.source}`,
            disclaimer,
        ].filter(Boolean).join('\n');
    }

    function topicReply(topic, disclaimer) {
        return [
            topic.answer,
            'Practical steps:',
            numbered(topic.steps),
            `Escalate to the flight surgeon or ground medical team: ${topic.escalate}`,
            `Source: ${topic.source}`,
            disclaimer,
        ].filter(Boolean).join('\n');
    }

    function clarifyReply(categories = []) {
        const labels = categories.map((category) => category.label).join(', ');
        if (!labels) return 'I am not sure what you mean. Could you clarify? You can also ask about your latest recorded metrics, such as heart rate, sleep, or blood pressure.';
        return `I am not sure what you mean. Did you mean one of these topics: ${labels}? You can also ask about your latest recorded metrics, such as heart rate, sleep, or blood pressure.`;
    }

    // Structured data for the UI cards; the reply text is still the fallback for every client.
    function metricCard(metricId, snapshot, time, now) {
        if (!snapshot || metricId === 'blood_pressure' || (time && !['latest', 'today'].includes(time))) return null;
        const metric = metrics.find((item) => item.id === metricId);
        const reading = (snapshot.readings || []).find((item) => item.code === metricId);
        const observed = reading ? new Date(reading.observed_at) : null;
        if (!metric || !reading || Number.isNaN(observed.getTime())) return null;
        if (time === 'today' && observed.toISOString().slice(0, 10) !== now.toISOString().slice(0, 10)) return null;
        const stale = Number.isFinite(reading.freshness_hours) && now.getTime() - observed.getTime() > reading.freshness_hours * 3600000;
        const ruleCode = { resting_heart_rate: 'resting_heart_rate_change', sleep_duration: 'sleep_fatigue_review', fatigue_rating: 'sleep_fatigue_review' }[metricId];
        const ruleState = snapshot.rule_states?.[ruleCode];
        // "normal" means no trend flag only; it is never a medical clearance.
        let status = 'normal';
        if (ruleState === 'triggered') status = 'watch';
        else if (stale || ruleState === 'insufficient_data') status = 'unknown';
        return {
            type: 'vitals', label: metric.label, value: reading.value, unit: reading.unit || '',
            observed_at: observed.toISOString(), source: reading.source || 'unknown', stale, status,
            history: snapshot.history?.[metricId] || [],
        };
    }

    const RULE_LABELS = {
        sleep_fatigue_review: 'sleep and fatigue trend',
        resting_heart_rate_change: 'resting heart rate trend',
    };

    // Explains only the rules already flagged in the cached snapshot; it never creates a new judgement.
    function alertsReply(snapshot, disclaimer) {
        if (!snapshot) return 'No personal snapshot is available on this device. Connect while signed in to refresh it.';
        const flagged = Object.entries(snapshot.rule_states || {})
            .filter(([, state]) => state === 'triggered')
            .map(([code]) => RULE_LABELS[code] || code.replace(/_/g, ' '));
        if (flagged.length === 0) {
            return ['No experimental trend rules are flagged for review in your cached snapshot. This is not a medical clearance.', disclaimer].filter(Boolean).join('\n');
        }
        return [
            `${flagged.length} trend ${flagged.length === 1 ? 'rule is' : 'rules are'} flagged for review: ${flagged.join(', ')}.`,
            'These flags come from simple trend rules and are not a diagnosis. Look at the related readings, and contact the flight surgeon or ground medical team if you have symptoms or the trend continues.',
            disclaimer,
        ].filter(Boolean).join('\n');
    }

    function respond(text, knowledge = {}, context = {}, snapshot = null, now = new Date()) {
        const k = { intents: [], topics: [], procedures: [], categories: [], disclaimer: '', ...knowledge };
        const result = select(text, k, context);
        const nextContext = result.intent === 'metric' ? { metric: result.entities.metric } : (result.intent === 'clarify' ? context : {});
        let reply;
        let card = null;
        if (result.intent === 'smalltalk') {
            reply = result.response;
        } else if (result.intent === 'arithmetic') {
            reply = solveBasicArithmetic(text);
        } else if (result.intent === 'emergency') {
            const procedure = k.procedures.find((item) => item.id === result.procedure);
            reply = emergencyReply(procedure, k.disclaimer);
            card = { type: 'emergency', title: procedure.title, steps: procedure.steps, escalate: procedure.escalate, source: procedure.source };
        } else if (result.intent === 'metric') {
            reply = metricResponse(result.entities.metric, snapshot, result.entities.time, text, now);
            card = metricCard(result.entities.metric, snapshot, result.entities.time, now);
        } else if (result.intent === 'alerts') {
            reply = alertsReply(snapshot, k.disclaimer);
        } else if (result.intent === 'clarify') {
            reply = clarifyReply(k.categories);
        } else {
            const topic = k.topics.find((item) => item.id === result.intent);
            if (topic) {
                reply = topicReply(topic, k.disclaimer);
                card = { type: 'checklist', title: topic.title, answer: topic.answer, category: topic.category, steps: topic.steps, escalate: topic.escalate, source: topic.source };
            } else {
                reply = k.intents.find((item) => item.id === result.intent)?.response;
            }
            if (!reply) throw new Error(`Missing response for intent: ${result.intent}`);
        }
        return { ...result, reply, card, context: nextContext };
    }

    const engine = { normalize, tokens, distance, extract, findProcedure, select, respond };
    root.OrbitEngine = engine;
    if (typeof module !== 'undefined' && module.exports) module.exports = engine;
})(globalThis);
