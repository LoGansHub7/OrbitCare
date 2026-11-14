import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import '../../public/chatbot/engine.js';

const engine = globalThis.OrbitEngine;
const loadJson = async (name) => JSON.parse(await readFile(new URL(`../../public/chatbot/${name}.json`, import.meta.url), 'utf8'));
const [intentFile, topicFile, procedureFile] = await Promise.all([loadJson('intents'), loadJson('health-knowledge'), loadJson('procedures')]);
const knowledge = {
    intents: intentFile.intents, topics: topicFile.topics, procedures: procedureFile.procedures,
    categories: topicFile.categories, disclaimer: topicFile.disclaimer,
};
const now = new Date('2026-11-14T12:00:00Z');

// Region names, slugs and systems mirror database/seeders/BodyRegionSeeder.php.
const SEEDED = [
    ['head-brain', 'Head & Brain', 'neurological', 'urgent'],
    ['eyes', 'Eyes', 'sensory', 'nominal'],
    ['heart', 'Heart', 'cardiovascular', 'review'],
    ['lungs', 'Lungs', 'respiratory', 'nominal'],
    ['spine', 'Spine', 'musculoskeletal', 'watch'],
    ['upper-body-muscles', 'Upper-body Muscles', 'musculoskeletal', 'review'],
    ['lower-body-muscles', 'Lower-body Muscles', 'musculoskeletal', 'nominal'],
    ['long-bones', 'Long Bones (Hips & Legs)', 'musculoskeletal', 'nominal'],
    ['digestive', 'Digestive System', 'digestive', 'nominal'],
    ['skin', 'Skin', 'integumentary', 'nominal'],
    ['whole-body-radiation', 'Whole Body / Radiation Exposure', 'radiation', 'nominal'],
];
const LEVELS = ['nominal', 'watch', 'review', 'urgent'];
const LABEL = { nominal: 'Nominal', watch: 'Monitoring', review: 'Review', urgent: 'Priority review' };

function region([slug, name, system, status]) {
    const levels = Object.fromEntries(LEVELS.map((level) => [level, {
        self_care_steps: [`${slug} ${level} first step`, `${slug} ${level} second step`],
        red_flags: [`${slug} ${level} red flag`],
        escalate_to: `${slug} ${level} escalation contact`,
    }]));
    const metrics = slug === 'whole-body-radiation'
        ? [{ label: 'Radiation dose rate', unit: 'mSv/day', value: 0.5, observed_at: '2026-11-14T10:00:00Z', source: 'simulation', freshness: 'current' }]
        : slug === 'skin'
            ? [{ label: 'Cabin CO2', unit: 'ppm', value: null, observed_at: null, source: null, freshness: 'no_data' }]
            : [{ label: `${name} metric`, unit: 'units', value: 7, observed_at: '2026-11-14T09:00:00Z', source: 'manual', freshness: 'stale' }];
    return {
        slug, name, body_system: system, status_level: status, function_text: `${name} function text.`,
        metrics, hazards: [{ slug: 'gravity', name: 'Gravity Alterations', why_it_matters: `${slug} hazard reason.` }],
        guidance: levels[status], guidance_levels: levels,
    };
}

const snapshot = {
    astronaut_id: 1, readings: [], checkin: null, rule_states: {}, body_regions: SEEDED.map(region),
    radiation: { dose_rate: 0.5, cumulative_dose: 25.09, recorded_at: '2026-11-14T10:00:00Z', is_simulated: true },
};
const ask = (text, context = {}, data = snapshot) => engine.respond(text, knowledge, context, data, now);

test('the whole body radiation question from the body map is answered from the region, not as a missing metric', () => {
    const result = ask('Tell me about my whole body / radiation exposure status');
    assert.equal(result.intent, 'region');
    assert.deepEqual(result.regions, ['whole-body-radiation']);
    assert.match(result.reply, /Whole Body \/ Radiation Exposure/);
    assert.match(result.reply, /Radiation dose rate\*\*: 0\.5 mSv\/day/);
    assert.match(result.reply, /source: simulation/);
    assert.doesNotMatch(result.reply, /No radiation dose value/);
    assert.equal(result.card.type, 'region');
});

for (const row of SEEDED) {
    const [slug, name, , status] = row;
    const data = region(row);
    const typed = name.toLowerCase();

    test(`${name}: every facet answers from its own cached data`, () => {
        const cases = [
            [`Tell me about my ${typed}`, [name, LABEL[status], 'What it does', 'Current vitals', 'What affects this in space', 'What you can do now', 'When to escalate', data.guidance.self_care_steps[0], data.guidance.red_flags[0]]],
            [`What does my ${typed} do?`, [data.function_text]],
            [`What are my ${typed} vitals?`, ['Current vitals', data.metrics[0].label]],
            [`Which hazards affect my ${typed}?`, ['What affects this in space', `${slug} hazard reason.`]],
            [`What should I do for my ${typed}?`, ['What you can do now', data.guidance.self_care_steps[0], data.guidance.self_care_steps[1]]],
            [`When should I escalate ${typed} problems?`, ['When to escalate', data.guidance.red_flags[0], data.guidance.escalate_to]],
            [`What do all the status levels mean for my ${typed}?`, LEVELS.flatMap((level) => [LABEL[level], data.guidance_levels[level].self_care_steps[0], data.guidance_levels[level].red_flags[0]])],
            ...LEVELS.map((level) => [`What should I do if my ${typed} is ${level === 'urgent' ? 'urgent' : level}?`, [LABEL[level], data.guidance_levels[level].self_care_steps[0], data.guidance_levels[level].red_flags[0], data.guidance_levels[level].escalate_to]]),
        ];
        for (const [question, expected] of cases) {
            const result = ask(question);
            assert.equal(result.intent, 'region', question);
            assert.deepEqual(result.regions, [slug], question);
            for (const fragment of expected) assert.ok(result.reply.includes(fragment), `"${question}" should include "${fragment}"\n${result.reply}`);
        }
    });

    test(`${name}: follow-ups keep the region in context`, () => {
        const first = ask(`Tell me about my ${typed}`);
        assert.deepEqual(first.context, { region: slug });
        const escalate = ask('When should I escalate?', first.context);
        assert.equal(escalate.intent, 'region');
        assert.ok(escalate.reply.includes(data.guidance.red_flags[0]));
        const steps = ask('What should I do?', first.context);
        assert.ok(steps.reply.includes(data.guidance.self_care_steps[0]));
        const hazards = ask('Why does it matter in space?', first.context);
        assert.ok(hazards.reply.includes(`${slug} hazard reason.`));
    });
}

test('status wording stays honest for every level', () => {
    assert.match(ask('Tell me about my spine').reply, /not the same as normal/);
    assert.match(ask('Tell me about my eyes').reply, /not a medical clearance/);
    assert.match(ask('Tell me about my heart').reply, /experimental trend rule linked to this region is flagged for review/);
    assert.match(ask('Tell me about my head & brain').reply, /priority-review trend rule/);
    assert.match(ask('Tell me about my skin').reply, /no value recorded in your body map data yet/);
    assert.match(ask('Tell me about my heart').reply, /\(source: manual, stale\)/);
});

test('whole-body questions summarise every region by status', () => {
    for (const question of ['Which body parts need review?', 'how is my body', 'show me the body map', 'every body part', 'give me a summary of my body']) {
        const result = ask(question);
        assert.equal(result.intent, 'body_overview', question);
        assert.match(result.reply, /Body map summary: 11 regions/);
        assert.match(result.reply, /Priority review \(1\)\*\*: Head & Brain/);
        assert.match(result.reply, /Review \(2\)\*\*: Heart, Upper-body Muscles/);
        assert.match(result.reply, /Monitoring \(1\)\*\*: Spine/);
        assert.match(result.reply, /Nominal \(7\)/);
        assert.match(result.reply, /Tell me about my head & brain/);
    }
    const calm = { ...snapshot, body_regions: SEEDED.map((row) => region([row[0], row[1], row[2], 'nominal'])) };
    assert.match(ask('which body parts need review', {}, calm).reply, /No region is flagged for review/);
    assert.match(ask('which body parts need review', {}, null).reply, /No body map data is available/);
});

test('ambiguous body words list the matching regions and specific ones pick one', () => {
    const muscles = ask('how are my muscles doing');
    assert.equal(muscles.intent, 'region');
    assert.deepEqual(muscles.regions.sort(), ['lower-body-muscles', 'upper-body-muscles']);
    assert.match(muscles.reply, /Which one do you mean/);
    assert.deepEqual(ask('tell me about my arm muscles').regions, ['upper-body-muscles']);
    assert.deepEqual(ask('tell me about my thighs').regions, ['lower-body-muscles']);
    assert.deepEqual(ask('what about my hips').regions, ['long-bones']);
    assert.deepEqual(ask('tell me about my whole body').regions, ['whole-body-radiation']);
    assert.deepEqual(ask('tell me about my brain').regions, ['head-brain']);
    assert.deepEqual(ask('tell me about my stomach').regions, ['digestive']);
    assert.deepEqual(ask('my lungs').regions, ['lungs']);
    assert.equal(ask('my legs').regions.length, 2);
});

test('existing answers are unchanged when a body map snapshot is present', () => {
    const questions = [
        'what is my heart rate', 'what is my hart rate?', 'how do I protect my bones and muscles', 'how does the heart change in microgravity',
        'my head hurts', 'I have a headache', 'my vision is blurry', 'how do I reduce my radiation exposure', 'what radiation protection should I follow',
        'what is cabin CO2 exposure', 'how do I stop an infection', 'what exercise prevents muscle loss', 'I feel dizzy when I stand up',
        'how can I sleep better in orbit', 'explain my alerts', 'hello', 'what can you do', 'xyzzy plugh',
    ];
    for (const question of questions) {
        const without = engine.respond(question, knowledge, {}, { ...snapshot, body_regions: undefined }, now);
        const withMap = ask(question);
        assert.equal(withMap.intent, without.intent, question);
        assert.equal(withMap.reply, without.reply, question);
    }
    for (const question of ['I have chest pain', 'I cannot breathe properly', 'severe bleeding from my arm']) {
        assert.equal(ask(question).intent, 'emergency', question);
    }
});

test('whole-body wording opens the region while personal dose questions use the recorded radiation reading', () => {
    for (const question of ['my whole body', 'tell me about my entire body', 'whole body radiation exposure status']) {
        const result = ask(question);
        assert.equal(result.intent, 'region', question);
        assert.deepEqual(result.regions, ['whole-body-radiation'], question);
    }
    const dose = ask('what is my radiation dose');
    assert.equal(dose.intent, 'metric');
    assert.match(dose.reply, /0\.5 mSv\/day/);
    assert.equal(ask('how do I reduce my radiation exposure').intent, 'radiation_exposure');
    assert.equal(ask('what radiation protection should I follow').intent, 'radiation_exposure');
});

test('without cached body map data the assistant does not invent region answers', () => {
    const bare = { ...snapshot, body_regions: undefined };
    assert.notEqual(engine.respond('Tell me about my whole body / radiation exposure status', knowledge, {}, bare, now).intent, 'region');
    assert.match(engine.respond('when should I escalate?', knowledge, { region: 'heart' }, bare, now).reply, /No body map data is available/);
    assert.equal(engine.respond('what about last week?', knowledge, { region: 'heart' }, snapshot, now).intent, 'clarify');
});
