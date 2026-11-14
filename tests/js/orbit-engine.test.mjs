import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import '../../public/chatbot/engine.js';

const engine = globalThis.OrbitEngine;
const loadJson = async (name) => JSON.parse(await readFile(new URL(`../../public/chatbot/${name}.json`, import.meta.url), 'utf8'));
const [intentFile, topicFile, procedureFile] = await Promise.all([
    loadJson('intents'), loadJson('health-knowledge'), loadJson('procedures'),
]);
const knowledge = {
    intents: intentFile.intents,
    topics: topicFile.topics,
    procedures: procedureFile.procedures,
    categories: topicFile.categories,
    disclaimer: topicFile.disclaimer,
};
const now = new Date('2026-11-14T12:00:00Z');
const snapshot = {
    readings: [
        { code: 'resting_heart_rate', value: 72, unit: 'bpm', observed_at: '2026-11-14T10:00:00Z', source: 'simulation', freshness_hours: 36 },
    ],
    checkin: {
        sleep_hours: 7.5, fatigue_rating: 2, exercise_minutes: 30,
        submitted_at: '2026-11-10T08:00:00Z', source: 'manual',
        freshness_hours: { sleep_duration: 36, fatigue_rating: 36, exercise_duration: 36 },
    },
    rule_states: { resting_heart_rate_change: 'triggered', sleep_fatigue_review: 'insufficient_data' },
};

test('normalizes text and extracts health entities', () => {
    assert.equal(engine.normalize('HEART-rate!!'), 'heart rate');
    const entities = engine.extract('My head hurts and I have a headache yesterday');
    assert.deepEqual(entities.body_parts, ['head']);
    assert.deepEqual(entities.symptoms, ['headache']);
    assert.equal(entities.time, 'yesterday');
});

test('matches typos and cites only the supplied latest personal snapshot', () => {
    const result = engine.respond('what is my hart rate?', knowledge, {}, snapshot, now);
    assert.equal(result.intent, 'metric');
    assert.equal(result.entities.metric, 'resting_heart_rate');
    assert.match(result.reply, /72 bpm/);
    assert.match(result.reply, /simulation/);
    assert.match(result.reply, /experimental trend rule requests review/);
    assert.match(result.reply, /not a diagnosis/);
    assert.equal(engine.respond('hart rate', knowledge, {}, snapshot, now).intent, 'metric');
});

test('follow-ups use context without fabricating historical or causal data', () => {
    const first = engine.respond('my heart rate', knowledge, {}, snapshot, now);
    const history = engine.respond('what about last week?', knowledge, first.context, snapshot, now);
    assert.equal(history.entities.metric, 'resting_heart_rate');
    assert.match(history.reply, /not last week history/);
    assert.doesNotMatch(history.reply, /72 bpm/);
    assert.match(engine.respond('why is it high?', knowledge, first.context, snapshot, now).reply, /cannot determine why/);
});

test('a topic question with "and" after a metric question is not treated as a follow-up', () => {
    const first = engine.respond('my heart rate', knowledge, {}, snapshot, now);
    const bones = engine.respond('How do I protect my bones and muscles?', knowledge, first.context, snapshot, now);
    assert.equal(bones.intent, 'bone_loss');
    assert.doesNotMatch(bones.reply, /72 bpm/);
});

test('reports stale and missing values without equating missing data with normal', () => {
    const sleep = engine.respond('my sleep', knowledge, {}, snapshot, now);
    assert.match(sleep.reply, /7.5 hours/);
    assert.match(sleep.reply, /stale/);
    assert.match(sleep.reply, /insufficient data/);
    assert.match(engine.respond('my temperature', knowledge, {}, snapshot, now).reply, /No body temperature value/);
    assert.match(engine.respond('my sleep today', knowledge, {}, snapshot, now).reply, /no sleep duration value from today/i);
});

test('extracts requested metric vocabulary without inventing unavailable measures', () => {
    for (const [question, metric] of [
        ['my blood pressure', 'blood_pressure'], ['my SpO2', 'spo2'], ['my temperature', 'body_temperature'],
        ['my radiation dose', 'radiation_dose_sim'], ['my hydration', 'hydration'], ['my weight', 'body_mass'],
    ]) {
        const result = engine.respond(question, knowledge, {}, snapshot, now);
        assert.equal(result.entities.metric, metric, question);
        assert.match(result.reply, /No .* available/, question);
    }
});

test('reports blood pressure components separately with their own timestamps', () => {
    const data = { readings: [
        { code: 'blood_pressure_systolic', label: 'Systolic', value: 120, unit: 'mmHg', observed_at: '2026-11-14T10:00:00Z', source: 'manual', freshness_hours: 36 },
    ] };
    const response = engine.respond('what is my blood pressure?', knowledge, {}, data, now);
    assert.match(response.reply, /Systolic: 120 mmHg/);
    assert.match(response.reply, /other component is unavailable/);
    assert.match(response.reply, /may not form a single measurement/);
});

test('explains only the alert rules flagged in the cached snapshot', () => {
    const flagged = { ...snapshot, rule_states: { sleep_fatigue_review: 'triggered', resting_heart_rate_change: 'clear' } };
    const response = engine.respond('Explain my alerts', knowledge, {}, flagged, now);
    assert.equal(response.intent, 'alerts');
    assert.match(response.reply, /1 trend rule is flagged for review: sleep and fatigue trend/);
    assert.doesNotMatch(response.reply, /resting heart rate/);
    assert.match(engine.respond('any alerts?', knowledge, {}, { ...snapshot, rule_states: {} }, now).reply, /No experimental trend rules are flagged/);
    assert.match(engine.respond('alerts', knowledge, {}, null, now).reply, /No personal snapshot/);
});

test('ambiguous and unrelated requests ask for clarification', () => {
    for (const text of ['purple telescope', 'my sleep and heart rate', 'tell me about stress and nutrition']) {
        const result = engine.respond(text, knowledge, {}, snapshot, now);
        assert.equal(result.intent, 'clarify', text);
        assert.match(result.reply, /Did you mean one of these topics/);
    }
});

test('urgent red flags take precedence over other intent matches', () => {
    for (const text of ['I have chest pain and want to sleep', "I can't breathe", 'severe bleeding', 'loss of consciousness']) {
        const result = engine.respond(text, knowledge, {}, snapshot, now);
        assert.equal(result.intent, 'emergency', text);
        assert.match(result.reply, /^EMERGENCY/);
    }
});

test('health topics and greetings match deterministically', () => {
    for (const [text, expected] of [
        ['hello', 'greeting'], ['tell me about radiation', 'radiation_exposure'], ['space exercise', 'muscle_countermeasures'],
        ['how does isolation affect crew', 'mental_isolation'], ['nutrition in space', 'nutrition_food'], ['I have a headache', 'symptom_general'],
    ]) assert.equal(engine.select(text, knowledge).intent, expected, text);
});
