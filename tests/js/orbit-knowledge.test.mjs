import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import '../../public/chatbot/engine.js';

const engine = globalThis.OrbitEngine;
const load = async (name) => JSON.parse(await readFile(new URL(`../../public/chatbot/${name}.json`, import.meta.url), 'utf8'));
const [intentFile, topicFile, procedureFile, faqFile] = await Promise.all([
    load('intents'), load('health-knowledge'), load('procedures'), load('faq'),
]);
const knowledge = {
    intents: intentFile.intents,
    topics: topicFile.topics,
    procedures: procedureFile.procedures,
    categories: topicFile.categories,
    disclaimer: topicFile.disclaimer,
};

test('health knowledge topics are complete and reference known categories', () => {
    const categoryIds = new Set(topicFile.categories.map((category) => category.id));
    const topicIds = new Set();
    assert.ok(topicFile.disclaimer.length > 0);
    for (const topic of topicFile.topics) {
        assert.ok(!topicIds.has(topic.id), `duplicate topic ${topic.id}`);
        topicIds.add(topic.id);
        assert.ok(categoryIds.has(topic.category), `unknown category for ${topic.id}`);
        assert.ok(topic.phrases.length >= 3, `too few phrases for ${topic.id}`);
        assert.ok(topic.steps.length >= 3 && topic.steps.length <= 4, `step count for ${topic.id}`);
        assert.ok(topic.answer && topic.escalate && topic.source, `missing field for ${topic.id}`);
    }
    for (const category of topicFile.categories) {
        assert.ok(topicFile.topics.some((topic) => topic.category === category.id), `empty category ${category.id}`);
    }
});

test('every topic id is unique across intents, topics and procedures', () => {
    const ids = [...intentFile.intents, ...topicFile.topics, ...procedureFile.procedures].map((item) => item.id);
    assert.equal(new Set(ids).size, ids.length);
});

test('FAQ questions point to existing topics', () => {
    const topicIds = new Set(topicFile.topics.map((topic) => topic.id));
    assert.ok(faqFile.questions.length >= 10);
    for (const item of faqFile.questions) {
        assert.ok(topicIds.has(item.topic), `FAQ references unknown topic ${item.topic}`);
    }
});

test('each topic routes to itself from its first phrase', () => {
    for (const topic of topicFile.topics) {
        const result = engine.respond(topic.phrases[0], knowledge, {}, null, new Date());
        assert.equal(result.intent, topic.id, topic.phrases[0]);
    }
});

test('each emergency procedure is detected from every trigger', () => {
    for (const procedure of procedureFile.procedures) {
        assert.ok(procedure.steps.length >= 3, `steps for ${procedure.id}`);
        for (const trigger of procedure.triggers) {
            const found = engine.findProcedure(engine.normalize(`I think ${trigger} now`), procedureFile.procedures);
            assert.equal(found?.id, procedure.id, trigger);
        }
    }
});

test('emergency replies include steps, escalation and the disclaimer', () => {
    const result = engine.respond('I have chest pain', knowledge, {}, null, new Date());
    assert.equal(result.intent, 'emergency');
    assert.match(result.reply, /^EMERGENCY: Chest pain or pressure/);
    assert.match(result.reply, /1\. /);
    assert.match(result.reply, /Escalate:/);
    assert.ok(result.reply.includes(topicFile.disclaimer));
});

test('topic replies include steps, escalation and source', () => {
    const result = engine.respond('tell me about radiation', knowledge, {}, null, new Date());
    assert.equal(result.intent, 'radiation_exposure');
    assert.match(result.reply, /Practical steps:/);
    assert.match(result.reply, /Escalate to the flight surgeon/);
    assert.match(result.reply, /Source: /);
});
