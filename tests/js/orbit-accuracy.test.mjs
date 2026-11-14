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

// Each case: [question, expected intent]. Expected values are topic ids, or one of
// metric, emergency, alerts, clarify, greeting, help.
const CASES = [
    ['hello', 'greeting'], ['hi there', 'greeting'], ['good morning orbit', 'greeting'],
    ['what can you do', 'help'], ['show me the menu', 'help'], ['what topics are there', 'help'],
    ['what is my heart rate', 'metric'], ['what is my hart rate?', 'metric'], ['my blood pressure today', 'metric'],
    ['what is my spo2', 'metric'], ['show my body temperature', 'metric'], ['how much did I sleep', 'metric'],
    ['what is my radiation dose', 'metric'], ['what is my weight', 'metric'], ['what is my oxygen saturation', 'metric'],
    ['why is my heart rate high', 'metric'], ['what about last week', 'clarify'],
    ['I have chest pain', 'emergency'], ['I cannot breathe properly', 'emergency'], ['I passed out and fainted', 'emergency'],
    ['there is severe bleeding from my arm', 'emergency'], ['I think I am having a heart attack', 'emergency'],
    ['explain my alerts', 'alerts'], ['are there any warnings for me', 'alerts'], ['any flags on my readings', 'alerts'],
    ['my head hurts', 'symptoms'], ['I have a headache', 'symptoms'], ['I feel dizzy when I stand up', 'cardiovascular'],
    ['how do I protect my bones and muscles', 'bone_muscle'], ['what exercise prevents muscle loss', 'bone_muscle'],
    ['how does the heart change in microgravity', 'cardiovascular'], ['what is orthostatic intolerance', 'cardiovascular'],
    ['what radiation protection should I follow', 'radiation'], ['how do I reduce my radiation exposure', 'radiation'],
    ['how can I sleep better in orbit', 'sleep'], ['I cannot sleep at night', 'sleep'], ['what is the circadian rhythm', 'sleep'],
    ['I have space sickness and nausea', 'vision'], ['my vision is blurry', 'vision'], ['what is SANS', 'vision'],
    ['I feel lonely and stressed', 'mental'], ['how do I cope with isolation', 'mental'], ['crew conflict is making me angry', 'mental'],
    ['what should I eat in space', 'nutrition'], ['how much water should I drink', 'nutrition'], ['how do I manage my weight', 'nutrition'],
    ['I cut my finger', 'infection_injury'], ['how do I stop an infection', 'infection_injury'], ['I got a burn, first aid please', 'infection_injury'],
    ['what is cabin CO2 exposure', 'cabin_air'], ['the air smells strange in the cabin', 'cabin_air'],
    ['what symptoms should I watch for', 'symptoms'], ['I feel tired and weak', 'symptoms'],
    ['', 'clarify'], ['xyzzy plugh', 'clarify'],
];

test('intent accuracy across sample questions', () => {
    const results = CASES.map(([question, expected]) => {
        const response = engine.respond(question, knowledge, {}, null, now);
        const topic = knowledge.topics.find((item) => item.id === response.intent);
        const actual = topic ? topic.category : response.intent;
        return { question, expected, actual, pass: actual === expected };
    });
    const failures = results.filter((item) => !item.pass);
    const accuracy = ((results.length - failures.length) / results.length) * 100;
    console.log(`Intent accuracy: ${accuracy.toFixed(1)}% (${results.length - failures.length}/${results.length})`);
    for (const failure of failures) {
        console.log(`  MISS "${failure.question}" expected ${failure.expected}, got ${failure.actual}`);
    }
    assert.ok(CASES.length >= 50, 'suite must hold at least 50 questions');
    assert.ok(accuracy >= 90, `accuracy ${accuracy.toFixed(1)}% below 90%`);
});
