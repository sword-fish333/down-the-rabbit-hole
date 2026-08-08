<?php

/*
 * Romanian validation messages — trimmed to the rules this app actually uses.
 * Anything not listed falls back to lang/en, so an unusual rule reads English
 * rather than as a raw key.
 *
 * `attributes` matters as much as the messages: without it Laravel prints the
 * column name, and "Câmpul first_name este obligatoriu" is where a form starts
 * feeling machine-translated.
 */
return [
    'accepted' => 'Trebuie să accepți :attribute.',
    'between' => [
        'numeric' => 'Câmpul :attribute trebuie să fie între :min și :max.',
        'file' => 'Fișierul :attribute trebuie să aibă între :min și :max kilobytes.',
        'string' => 'Câmpul :attribute trebuie să aibă între :min și :max caractere.',
        'array' => 'Câmpul :attribute trebuie să aibă între :min și :max elemente.',
    ],
    'not_in' => 'Valoarea aleasă pentru :attribute nu este validă.',
    'after' => 'Câmpul :attribute trebuie să fie o dată după :date.',
    'after_or_equal' => 'Câmpul :attribute trebuie să fie :date sau mai târziu.',
    'array' => 'Câmpul :attribute trebuie să fie o listă.',
    'before' => 'Câmpul :attribute trebuie să fie o dată înainte de :date.',
    'before_or_equal' => 'Câmpul :attribute trebuie să fie cel târziu :date.',
    'boolean' => 'Câmpul :attribute trebuie să fie da sau nu.',
    'confirmed' => 'Confirmarea pentru :attribute nu coincide.',
    'date' => 'Câmpul :attribute nu este o dată validă.',
    'date_format' => 'Câmpul :attribute nu respectă formatul :format.',
    'digits' => 'Câmpul :attribute trebuie să aibă :digits cifre.',
    'email' => 'Câmpul :attribute trebuie să fie o adresă de e-mail validă.',
    'exists' => 'Valoarea aleasă pentru :attribute nu este validă.',
    'file' => 'Câmpul :attribute trebuie să conțină un fișier valid.',
    'filled' => 'Câmpul :attribute este obligatoriu.',
    'image' => 'Câmpul :attribute trebuie să conțină o imagine validă.',
    'in' => 'Valoarea aleasă pentru :attribute nu este validă.',
    'integer' => 'Câmpul :attribute trebuie să fie un număr întreg.',
    'max' => [
        'numeric' => 'Câmpul :attribute nu poate fi mai mare de :max.',
        'file' => 'Fișierul :attribute nu poate depăși :max kilobytes.',
        'string' => 'Câmpul :attribute nu poate depăși :max caractere.',
        'array' => 'Câmpul :attribute nu poate avea mai mult de :max elemente.',
    ],
    'min' => [
        'numeric' => 'Câmpul :attribute trebuie să fie cel puțin :min.',
        'file' => 'Fișierul :attribute trebuie să aibă cel puțin :min kilobytes.',
        'string' => 'Câmpul :attribute trebuie să aibă cel puțin :min caractere.',
        'array' => 'Câmpul :attribute trebuie să conțină cel puțin :min elemente.',
    ],
    'mimes' => 'Câmpul :attribute trebuie să fie un fișier de tipul: :values.',
    'mimetypes' => 'Câmpul :attribute trebuie să fie un fișier de tipul: :values.',
    'numeric' => 'Câmpul :attribute trebuie să fie un număr.',
    'password' => [
        'letters' => 'Câmpul :attribute trebuie să conțină cel puțin o literă.',
        'mixed' => 'Câmpul :attribute trebuie să conțină cel puțin o literă mare și una mică.',
        'numbers' => 'Câmpul :attribute trebuie să conțină cel puțin o cifră.',
        'symbols' => 'Câmpul :attribute trebuie să conțină cel puțin un simbol.',
        'uncompromised' => 'Valoarea aleasă pentru :attribute a apărut într-o breșă de date. Alege o parolă diferită.',
    ],
    'phone' => 'Câmpul :attribute trebuie să fie un număr de telefon valid.',
    'prohibited' => 'Câmpul :attribute nu este permis.',
    'regex' => 'Formatul câmpului :attribute nu este valid.',
    'required' => 'Câmpul :attribute este obligatoriu.',
    'required_if' => 'Câmpul :attribute este obligatoriu.',
    'required_if_accepted' => 'Câmpul :attribute este obligatoriu.',
    'size' => [
        'string' => 'Câmpul :attribute trebuie să aibă :size caractere.',
    ],
    'string' => 'Câmpul :attribute trebuie să fie text.',
    'current_password' => 'Parola introdusă este incorectă.',
    'unique' => 'Valoarea pentru :attribute este deja utilizată.',
    'url' => 'Câmpul :attribute trebuie să fie un link valid.',

    'custom' => [],

    'attributes' => [
        'first_name' => 'prenume',
        'last_name' => 'nume',
        'name' => 'nume',
        'email' => 'email',
        'phone' => 'telefon',
        'password' => 'parolă',
        'password_confirmation' => 'confirmarea parolei',
        'current_password' => 'parola actuală',
        'profile_image' => 'poza de profil',
        'prompt' => 'subiect',
        'answer' => 'răspuns',
        'self_rating' => 'nivelul tău de încredere',
        'learning_mode_id' => 'modul de predare',
        'parent_id' => 'dosarul părinte',
        'folder_id' => 'dosar',
        'message' => 'mesaj',
    ],
];
