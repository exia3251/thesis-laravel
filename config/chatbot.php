<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Groq, which answers first
    |--------------------------------------------------------------------------
    | The assistant has two halves. This one reads the question and writes a
    | sentence; the other matches keywords against a table of answers the
    | business wrote itself.
    |
    | With a key set, Groq is what answers. It is handed the shop's own
    | answers, its catalogue arranged by viscosity grade, and the vehicles the
    | shop has looked up, and it is told to answer from those and nothing else.
    | That is what lets it say whether a particular car suits a particular oil
    | on the shelf, which a keyword list cannot do.
    |
    | Without a key -- or with no internet, or when Groq is slow, refuses, or
    | answers with nonsense -- the keyword half answers exactly as it always
    | has. That path is not a leftover. It is what runs when this is shown on
    | a laptop in a room with no wifi.
    |
    | Questions about an order, a balance, a price or a stock level never come
    | here at all. Those are read from the database, where the real figure is.
    */
    'groq' => [
        'key' => env('GROQ_API_KEY'),

        /*
         * Settable, because Groq's line-up changes faster than this project
         * will. This one is free on the developer tier and the strongest of
         * the free ones; `php artisan assistant:check` lists what a key can
         * actually reach if this stops existing.
         */
        'model' => env('GROQ_MODEL', 'openai/gpt-oss-120b'),

        // Short on purpose. Somebody is watching a typing indicator, and a
        // slow answer is worse than the list of suggestions it replaces.
        'timeout' => (int) env('GROQ_TIMEOUT', 8),

        // Two to four sentences is the whole brief, so this is generous
        // rather than tight -- a reply cut off mid-word is thrown away, and
        // that is a worse outcome than a few tokens spent.
        'max_output_tokens' => (int) env('GROQ_MAX_TOKENS', 700),

        /*
         * How hard to think before answering, for the models that take it.
         * Empty by default: an OpenAI-compatible endpoint refuses a field it
         * does not recognise rather than ignoring it, so a setting that suits
         * one model turns every answer into a 400 on another. "low" is worth
         * setting for the gpt-oss models, where it cuts the wait noticeably.
         */
        'reasoning_effort' => env('GROQ_REASONING_EFFORT'),
    ],

];
