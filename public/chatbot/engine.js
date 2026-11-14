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

    // ---------- body map regions ----------
    // Extra names per region slug. The names, statuses, vitals and guidance themselves always come from the
    // cached snapshot (snapshot.body_regions), so the assistant never invents body-map facts.
    const REGION_SYNONYMS = {
        'head-brain': ['head', 'brain', 'mind', 'cognition', 'neurological'],
        eyes: ['eye', 'eyes', 'eyesight', 'sight'],
        heart: ['heart', 'cardiac'],
        lungs: ['lung', 'lungs', 'respiratory'],
        spine: ['spine', 'spinal', 'backbone', 'back', 'vertebrae'],
        'upper-body-muscles': ['upper body', 'arm', 'arms', 'shoulder', 'shoulders', 'biceps', 'upper muscles', 'muscles'],
        'lower-body-muscles': ['lower body', 'leg', 'legs', 'thigh', 'thighs', 'calf', 'calves', 'lower muscles', 'muscles'],
        'long-bones': ['bones', 'bone density', 'skeleton', 'hip', 'hips', 'leg', 'legs', 'femur'],
        digestive: ['digestion', 'stomach', 'gut', 'abdomen', 'bowel', 'intestines'],
        skin: ['skin', 'rash'],
        'whole-body-radiation': ['whole body', 'entire body', 'full body'],
    };
    const STATUS_TEXT = {
        nominal: ['Nominal', 'No implemented trend rule is flagged for this region right now. This is not a medical clearance.'],
        watch: ['Monitoring', 'There is too little recent data for the rules to judge this region, so it is only being monitored. Missing data is not the same as normal.'],
        review: ['Review', 'An experimental trend rule linked to this region is flagged for review.'],
        urgent: ['Priority review', 'A priority-review trend rule linked to this region is flagged. Review the evidence now and follow the escalation steps.'],
    };
    const LEVELS = ['nominal', 'watch', 'review', 'urgent'];
    const LEVEL_WORDS = [
        ['urgent', 'urgent|priority(?: review)?'], ['review', 'review'], ['watch', 'watch|monitoring'], ['nominal', 'nominal|normal'],
    ];

    function regionAliases(region) {
        const names = new Set();
        const full = normalize(region.name || '');
        if (full) names.add(full);
        // Name fragments ("Head", "Brain") are aliases only when a single word; longer fragments such as
        // "Radiation Exposure" would otherwise hijack generic topic questions.
        String(region.name || '').split(/[&/()]|\band\b/i).map(normalize).filter((part) => part && !part.includes(' ')).forEach((part) => names.add(part));
        (REGION_SYNONYMS[region.slug] || []).forEach((alias) => names.add(normalize(alias)));
        return { full, aliases: [...names] };
    }

    function findRegions(text, snapshot) {
        const regions = snapshot?.body_regions;
        if (!Array.isArray(regions) || !regions.length) return [];
        const hits = [];
        for (const region of regions) {
            const { full, aliases } = regionAliases(region);
            let weight = 0;
            let strong = false;
            for (const alias of aliases) {
                if (phraseScore(text, alias) < 0.75) continue;
                const size = tokens(alias).length;
                weight += size;
                if (size >= 2) strong = true;
            }
            if (weight) hits.push({ region, weight, strong, named: tokens(full).length >= 2 && phraseScore(text, full) >= 0.75 });
        }
        return hits;
    }

    // via: 'name' (full multi-word name typed), 'strong' (multi-word alias) or 'alias' (a single word such as "heart").
    function pickRegions(hits) {
        if (!hits.length) return { via: null, regions: [] };
        const named = hits.filter((hit) => hit.named);
        const pool = named.length ? named : hits;
        const top = Math.max(...pool.map((hit) => hit.weight));
        const best = pool.filter((hit) => hit.weight === top);
        return {
            via: named.length ? 'name' : (best.some((hit) => hit.strong) ? 'strong' : 'alias'),
            regions: best.map((hit) => hit.region),
        };
    }

    function isOverallQuery(normalized) {
        return /\b(?:body map|body overview|body summary|(?:summary|overview) of my body|my body overall|overall body|(?:all|every|each)(?: of)?(?: my)?(?: body)? (?:parts?|regions?|organs?|systems?|areas?)|which (?:body )?(?:parts?|regions?|organs?|systems?|areas?)|how (?:s|is|are) my body|any (?:body )?(?:parts?|regions?) (?:that )?need)\b/.test(normalized);
    }

    // `explicit` is false when the text names no particular aspect, which means "tell me everything".
    function facetOf(normalized) {
        const levelPattern = LEVEL_WORDS.map(([, pattern]) => pattern).join('|');
        const conditional = new RegExp(`\\b(?:if|when|whenever|suppose)\\b.*\\b(?:is|are|was|were|goes|go|becomes|become|turns|turn|gets|get)\\b.*\\b(?:${levelPattern})\\b`);
        const levelNoun = new RegExp(`\\b(?:${levelPattern}) (?:status|level|state)\\b`);
        if (conditional.test(normalized) || levelNoun.test(normalized)) {
            const level = LEVEL_WORDS.find(([, pattern]) => new RegExp(`\\b(?:${pattern})\\b`).test(normalized))?.[0];
            if (level) return { facet: 'level', level, explicit: true };
        }
        if (/\b(?:status levels?|all (?:the )?(?:status(?:es)?|levels?)|each (?:status|level)|every (?:status|level)|different (?:status(?:es)?|levels?)|what do (?:the )?(?:status(?:es)?|levels?|badges?|colou?rs?) mean|what if (?:it|this|that|my \w+) (?:gets?|becomes?|worsens?)|gets? worse)\b/.test(normalized)) {
            return { facet: 'levels', level: null, explicit: true };
        }
        const cues = [
            ['escalate', /\b(?:escalat\w*|red flags?|warning signs?|danger signs?|when (?:should|do|to) i (?:call|worry|get help|seek|report|tell)|who (?:should|do) i (?:call|contact|tell|report)|see (?:a )?(?:doctor|medical|flight surgeon)|medical officer|flight surgeon)\b/],
            ['actions', /\b(?:what (?:can|should|could|do) i do|self care|steps?|advice|tips?|how (?:can|do|should) i (?:protect|improve|look after|care|keep|support|manage|reduce|prevent)|protect|improve|look after|action)\b/],
            ['hazards', /\b(?:hazards?|affect(?:s|ing|ed)?|microgravity|spaceflight|in space|why (?:does|do|is|are) (?:it|this|that|my \w+) (?:matter|important)|why it matters|matters?|risks?|causes?|causing)\b/],
            ['vitals', /\b(?:vitals?|readings?|metrics?|values?|numbers?|measurements?|trends?|history|chart|data|how (?:high|low))\b/],
            ['function', /\b(?:what does (?:it|this|that|my \w+|the \w+) do|what is (?:it|this|that) for|function|purpose|role|how does (?:it|this|that|my \w+) work|what is the \w+ for)\b/],
        ];
        const cue = cues.find(([, pattern]) => pattern.test(normalized));
        return cue ? { facet: cue[0], level: null, explicit: true } : { facet: 'overview', level: null, explicit: false };
    }

    function regionIntent(slugs, normalized, entities) {
        const { facet, level } = facetOf(normalized);
        return { intent: 'region', regions: slugs, facet, level, confidence: 1, entities };
    }

    function whenText(iso) {
        const date = new Date(iso);
        return Number.isNaN(date.getTime()) ? null : date.toISOString();
    }

    function regionMetricLine(metric) {
        const label = metric.label || 'Metric';
        if (metric.value === null || metric.value === undefined) return `- **${label}**: no value recorded in your body map data yet.`;
        const recorded = whenText(metric.observed_at);
        const notes = [metric.source ? `source: ${metric.source}` : null, metric.freshness === 'stale' ? 'stale' : null].filter(Boolean);
        return `- **${label}**: ${metric.value} ${metric.unit || ''}`.trimEnd() + `${recorded ? `, recorded ${recorded}` : ''}${notes.length ? ` (${notes.join(', ')})` : ''}`;
    }

    function statusOf(region) {
        return STATUS_TEXT[region.status_level] || STATUS_TEXT.watch;
    }

    function regionHeader(region) {
        const system = region.body_system ? ` (${String(region.body_system).replace(/_/g, ' ')})` : '';
        return `**${region.name}**${system}. Status: **${statusOf(region)[0]}**.`;
    }

    function guidanceBlocks(guidance) {
        const steps = guidance?.self_care_steps || [];
        const flags = guidance?.red_flags || [];
        return {
            steps: ['**What you can do now**', ...(steps.length ? steps.map((step, index) => `${index + 1}. ${step}`) : ['No guidance is stored for this status yet.'])],
            escalate: ['**When to escalate**', ...(flags.length ? flags.map((flag) => `- ${flag}`) : ['- No red flags are stored for this status yet.']), ...(guidance?.escalate_to ? [`Escalate to: ${guidance.escalate_to}.`] : [])],
        };
    }

    function hazardBlock(region) {
        const hazards = region.hazards || [];
        return ['**What affects this in space**', ...(hazards.length ? hazards.map((item) => `- **${item.name}**: ${item.why_it_matters}`) : ['- No spaceflight hazards are linked to this region yet.'])];
    }

    function vitalsBlock(region) {
        const metrics = region.metrics || [];
        return ['**Current vitals**', ...(metrics.length ? metrics.map(regionMetricLine) : ['- No metrics are linked to this region yet.'])];
    }

    function levelBlock(region, level, disclaimer) {
        const entry = region.guidance_levels?.[level] || (region.status_level === level ? region.guidance : null);
        const { steps, escalate } = guidanceBlocks(entry);
        if (!entry) return [`${region.name} guidance for ${STATUS_TEXT[level][0]} is not available in the cached snapshot. Reconnect to refresh it.`];
        return [`If **${region.name}** reaches **${STATUS_TEXT[level][0]}**: ${STATUS_TEXT[level][1]}`, ...steps, ...escalate, disclaimer];
    }

    function allLevelsBlock(region, disclaimer) {
        const lines = [`How **${region.name}** guidance changes with its status (currently **${statusOf(region)[0]}**):`];
        for (const level of LEVELS) {
            const entry = region.guidance_levels?.[level] || (region.status_level === level ? region.guidance : null);
            lines.push(`**${STATUS_TEXT[level][0]}${region.status_level === level ? ' (current)' : ''}**`);
            if (!entry) {
                lines.push('- Not available in the cached snapshot.');
                continue;
            }
            lines.push(`- Do: ${(entry.self_care_steps || []).join(' ') || 'No steps stored.'}`);
            lines.push(`- Watch for: ${(entry.red_flags || []).join(' ') || 'No red flags stored.'}`);
            if (entry.escalate_to) lines.push(`- Escalate to: ${entry.escalate_to}`);
        }
        lines.push(disclaimer);
        return lines;
    }

    function regionReply(region, facet, level, disclaimer) {
        const [label, meaning] = statusOf(region);
        const { steps, escalate } = guidanceBlocks(region.guidance);
        let lines;
        switch (facet) {
        case 'function':
            lines = [`**${region.name}**: ${region.function_text || 'No description is stored for this region.'}`];
            break;
        case 'vitals':
            lines = [`${regionHeader(region)}`, ...vitalsBlock(region), 'These are your latest recorded values and are not a diagnosis.'];
            break;
        case 'hazards':
            lines = [`**${region.name}**`, ...hazardBlock(region)];
            break;
        case 'actions':
            lines = [`${regionHeader(region)}`, ...steps, ...escalate];
            break;
        case 'escalate':
            lines = [`${regionHeader(region)}`, ...escalate, disclaimer];
            break;
        case 'level':
            lines = levelBlock(region, level, disclaimer);
            break;
        case 'levels':
            lines = allLevelsBlock(region, disclaimer);
            break;
        default:
            lines = [
                regionHeader(region), meaning,
                `What it does: ${region.function_text || 'No description is stored for this region.'}`,
                ...vitalsBlock(region), ...hazardBlock(region), ...steps, ...escalate, disclaimer,
            ];
        }
        return { text: lines.filter(Boolean).join('\n'), label };
    }

    function regionAnswer(result, snapshot, disclaimer) {
        const regions = (snapshot?.body_regions || []).filter((region) => result.regions.includes(region.slug));
        if (!regions.length) {
            return { reply: 'No body map data is available in the cached snapshot on this device. Reconnect while signed in so ORBIT can refresh it.', card: null };
        }
        if (regions.length > 1) {
            const lines = ['That could mean more than one body map region. Which one do you mean?',
                ...regions.map((region) => `- **${region.name}**: ${statusOf(region)[0]}. Ask "Tell me about my ${region.name.toLowerCase()}".`)];
            return { reply: lines.join('\n'), card: null };
        }
        const region = regions[0];
        const { text } = regionReply(region, result.facet, result.level, disclaimer);
        const card = {
            type: 'region', title: region.name, body_system: String(region.body_system || '').replace(/_/g, ' '),
            status_level: region.status_level, escalate: region.guidance?.escalate_to || 'Crew medical officer',
        };
        return { reply: text, card };
    }

    function overviewReply(snapshot, disclaimer) {
        const regions = snapshot?.body_regions;
        if (!Array.isArray(regions) || !regions.length) {
            return 'No body map data is available in the cached snapshot on this device. Reconnect while signed in so ORBIT can refresh it.';
        }
        const lines = [`Body map summary: ${regions.length} regions, using the same experimental rules as your alerts.`];
        for (const level of ['urgent', 'review', 'watch', 'nominal']) {
            const names = regions.filter((region) => region.status_level === level).map((region) => region.name);
            if (names.length) lines.push(`- **${STATUS_TEXT[level][0]} (${names.length})**: ${names.join(', ')}`);
        }
        const flagged = regions.filter((region) => ['urgent', 'review'].includes(region.status_level)).length;
        lines.push(flagged
            ? `${flagged} region${flagged > 1 ? 's are' : ' is'} flagged for review. Ask about one, for example "Tell me about my ${regions.find((region) => ['urgent', 'review'].includes(region.status_level)).name.toLowerCase()}".`
            : 'No region is flagged for review. "Nominal" means no implemented rule triggered, and "Monitoring" means there is too little recent data. Neither is a medical clearance.');
        lines.push(disclaimer);
        return lines.filter(Boolean).join('\n');
    }

    function select(text, knowledge, context = {}, snapshot = null) {
        const normalized = normalize(text);
        const entities = extract(text);
        if (isBasicArithmetic(text)) return { intent: 'arithmetic', confidence: 1, entities };
        const chat = basicConversation(normalized);
        if (chat) return { intent: 'smalltalk', response: chat, confidence: 1, entities };
        const procedure = findProcedure(normalized, knowledge.procedures);
        if (procedure) return { intent: 'emergency', procedure: procedure.id, confidence: 1, entities };
        if (/\b(?:alerts?|warnings?|flags?)\b/.test(normalized)) return { intent: 'alerts', confidence: 1, entities };
        // A body-map region named in full ("Whole Body / Radiation Exposure") or by a multi-word alias beats
        // metric matching; a single-word alias ("heart") defers to metrics and confident topics below.
        const found = pickRegions(findRegions(text, snapshot));
        if (found.via && found.via !== 'alias') return regionIntent(found.regions.map((region) => region.slug), normalized, entities);
        if (!found.via && isOverallQuery(normalized)) return { intent: 'body_overview', confidence: 1, entities };
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
        // Body-map regions answer only when no topic is a confident match, so "how do I protect my bones"
        // and "my head hurts" keep their existing topic answers.
        const topicConfident = scores.length && scores[0].confidence >= 0.75;
        if (!topicConfident) {
            const slugs = found.via === 'alias'
                ? found.regions.map((region) => region.slug)
                : (context.region && facetOf(normalized).explicit ? [context.region] : []);
            if (slugs.length) return regionIntent(slugs, normalized, entities);
        }
        if (!scores.length || scores[0].confidence < 0.75 || (tied && !resolved)) {
            return { intent: 'clarify', confidence: scores[0]?.confidence || 0, entities };
        }
        const { category, specificity, ...winner } = scores[0];
        return { ...winner, entities };
    }

    function basicConversation(text) {
        // Greetings and "what can you do" are answered by the greeting and help intents in intents.json.
        if (/^(how are you|how are you doing|whats up)$/.test(text)) return 'I’m here and ready to help. I can answer general questions, do simple calculations, or look up recorded crew data.';
        if (/^(who are you|what are you|what is orbit|what is orbitcare)$/.test(text)) return 'I’m ORBIT, the OrbitCare assistant. I can answer general spaceflight health questions from my local reference library, report available recorded data, and help with simple tasks. I cannot diagnose or replace the mission medical team.';
        if (/^(thank you|thanks|thank you very much)$/.test(text)) return 'You’re welcome!';
        if (/^(bye|goodbye|see you)$/.test(text)) return 'Goodbye. I’ll be here if you need help.';
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
        const result = select(text, k, context, snapshot);
        const nextContext = result.intent === 'metric'
            ? { metric: result.entities.metric }
            : (result.intent === 'region' && result.regions.length === 1 ? { region: result.regions[0] } : (result.intent === 'clarify' ? context : {}));
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
        } else if (result.intent === 'region') {
            ({ reply, card } = regionAnswer(result, snapshot, k.disclaimer));
        } else if (result.intent === 'body_overview') {
            reply = overviewReply(snapshot, k.disclaimer);
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
