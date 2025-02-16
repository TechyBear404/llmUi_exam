<?php

return [
    'accepted' => 'Le champ :attribute doit être accepté.',
    'accepted_if' => 'Le champ :attribute doit être accepté quand :other est :value.',
    'active_url' => 'Le champ :attribute n\'est pas une URL valide.',
    'after' => 'Le champ :attribute doit être une date postérieure à :date.',
    'after_or_equal' => 'Le champ :attribute doit être une date postérieure ou égale à :date.',
    'alpha' => 'Le champ :attribute doit contenir uniquement des lettres.',
    'alpha_dash' => 'Le champ :attribute doit contenir uniquement des lettres, des chiffres et des tirets.',
    'alpha_num' => 'Le champ :attribute doit contenir uniquement des lettres et des chiffres.',
    'array' => 'Le champ :attribute doit être un tableau.',
    'before' => 'Le champ :attribute doit être une date antérieure à :date.',
    'before_or_equal' => 'Le champ :attribute doit être une date antérieure ou égale à :date.',
    'between' => [
        'numeric' => 'La valeur de :attribute doit être comprise entre :min et :max.',
        'file' => 'La taille du fichier de :attribute doit être comprise entre :min et :max kilo-octets.',
        'string' => 'Le texte :attribute doit contenir entre :min et :max caractères.',
        'array' => 'Le tableau :attribute doit contenir entre :min et :max éléments.',
    ],
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'confirmed' => 'Le champ de confirmation :attribute ne correspond pas.',
    'date' => 'Le champ :attribute n\'est pas une date valide.',
    'date_equals' => 'Le champ :attribute doit être une date égale à :date.',
    'date_format' => 'Le champ :attribute ne correspond pas au format :format.',
    'different' => 'Les champs :attribute et :other doivent être différents.',
    'digits' => 'Le champ :attribute doit contenir :digits chiffres.',
    'digits_between' => 'Le champ :attribute doit contenir entre :min et :max chiffres.',
    'dimensions' => 'Les dimensions de l\'image :attribute ne sont pas valides.',
    'distinct' => 'Le champ :attribute a une valeur en double.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
    'ends_with' => 'Le champ :attribute doit se terminer par une des valeurs suivantes : :values',
    'exists' => 'Le champ :attribute sélectionné est invalide.',
    'file' => 'Le champ :attribute doit être un fichier.',
    'filled' => 'Le champ :attribute doit avoir une valeur.',
    'required' => 'Le champ :attribute est obligatoire.',
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'custom' => [
        'user_background' => [
            'required' => 'Votre contexte est requis.',
        ],
        'user_interests' => [
            'required' => 'Vos centres d\'intérêt sont requis.',
            'array' => 'Les centres d\'intérêt doivent être une liste.',
        ],
        'knowledge_levels' => [
            'required' => 'Les niveaux de connaissance sont requis.',
            'array' => 'Les niveaux de connaissance doivent être une liste.',
        ],
        'user_goals' => [
            'required' => 'Vos objectifs sont requis.',
        ],
        'assistant_background' => [
            'required' => 'Le contexte de l\'assistant est requis.',
        ],
        'assistant_personality' => [
            'required' => 'La personnalité de l\'assistant est requise.',
        ],
        'communication_style' => [
            'required' => 'Le style de communication est requis.',
        ],
        'response_style' => [
            'required' => 'Le style de réponse est requis.',
        ],
        'response_format' => [
            'required' => 'Le format de réponse est requis.',
        ],
    ],
];
