<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gemini, as a last resort
    |--------------------------------------------------------------------------
    | The assistant answers from a table of questions the business wrote and
    | from its own database. That is the whole of it when this is switched
    | off, which is the default: no key, no network, no change.
    |
    | With a key set, a question that matches nothing is passed to Gemini
    | along with the same answers and catalogue the assistant already holds,
    | and it is told to reply only from those. It is a fallback rather than a
    | replacement for two reasons. The curated answers are the business's own
    | words about its own policies, which no model can improve on; and a
    | question about an order, a price or a stock level is answered from the
    | database, where the real figure is.
    |
    | If the call fails, times out, or is refused, the assistant falls back to
    | what it did before: the closest questions it knows.
    */
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),

        // Settable, because model names change faster than this project will.
        'model' => env('GEMINI_MODEL', 'gemini-3.8-flash'),

        // Short on purpose. Somebody is watching a typing indicator, and a
        // slow answer is worse than the list of suggestions it replaces.
        'timeout' => (int) env('GEMINI_TIMEOUT', 8),

        // Two or three sentences of answer. The rest of the budget is for a
        // reasoning model's own deliberation, which is not shown but is paid
        // for out of the same allowance -- set too low, the answer is cut off
        // before it starts.
        'max_output_tokens' => (int) env('GEMINI_MAX_TOKENS', 800),

        /*
         * How much deliberating before answering, for a model that takes the
         * setting. Empty by default because generateContent refuses a field
         * it does not recognise rather than ignoring it, and the name has
         * moved between model generations -- a wrong guess here turns every
         * answer into a 400. With nothing sent, the guard on the way out is
         * what stops a model's own reasoning reaching a customer.
         */
        'thinking_level' => env('GEMINI_THINKING_LEVEL'),
    ],

];
