<?php

/*
|--------------------------------------------------------------------------
| Romanian — the learner-facing copy
|--------------------------------------------------------------------------
|
| Not a literal translation: the same product voice, in Romanian. The words of
| the descent are fixed here exactly as they are in English, so a learner never
| meets two names for one thing:
|
|   subject → subiect · layer → nivel  · descent → coborâre · descend → coboară
|   checkpoint → verificare · prove it → dovedește · mastery → stăpânire
|   surfaced → la suprafață · folder → dosar · streak → serie
|   learning record → parcurs
|
| Two rules this file is written against, both of them things a translation
| gets wrong and nobody notices in review:
|
|   1. `strat` is the only word for a layer. "Nivel" belongs to a game.
|   2. Nothing addressed to the learner carries a gendered participle. Romanian
|      makes "ești sigur" a guess about who is reading, so the copy asks
|      "câtă încredere ai" instead — and a name never takes a genitive
|      ("evidența lui Maria" is wrong), hence "Ce a dovedit :name".
|
| Plurals carry all three Romanian forms — 1 / 2–19 / 20 „de” — because
| ":count de straturi" is what a Romanian reads and "20 straturi" is not.
|
*/

return [

    'meta' => [
        'description' => 'Alege un subiect — sau lasă un link — și coboară. Fiecare nivel  îți cere să dovedești că l-ai înțeles înainte să se deschidă următorul, până ieși la suprafață expert.',
    ],

    'general' => [
        'skip-to-content' => 'Sari la conținut',
        'dismiss' => 'Închide',
        'required' => 'obligatoriu',
        'cancel' => 'Anulează',
        'submit-shortcut' => 'Enter trece pe rândul următor. Command, Control sau Option plus Enter trimite formularul.',
        'submit-shortcut-visual' => 'Enter = rând nou · ⌘ / Ctrl / Option + Enter = trimite',
    ],

    'navbar' => [
        'primary' => 'Principal',
        'language' => 'Limbă',
        'theme-toggle' => 'Comută între luminos și întunecat',
        'sign-in' => 'Intră în cont',
        'start' => 'Începe o coborâre',
        'sign-out' => 'Ieși din cont',
        'subjects' => 'Subiecte',
        'rankings' => 'Clasamente',
        'public-record' => 'Parcursul tău public',
        'profile' => 'Profil',
        'account' => 'Meniul contului',
        'streak-title' => 'Zile la rând cu un nivel  încheiat',
        'descent-days' => '{1} coborâre de o zi|[2,19] coborâre de :count zile|[20,*] coborâre de :count de zile',
    ],

    /*
    |--------------------------------------------------------------------------
    | Bara subiectelor
    |--------------------------------------------------------------------------
    */

    'sidebar' => [
        'label' => 'Subiectele tale',
        'open' => 'Deschide bara subiectelor',
        'close' => 'Închide bara subiectelor',
        'new' => 'Coborâre nouă',
        'subjects' => 'Subiecte',
        'view-label' => 'Cum să-ți arătăm subiectele',
        'view' => [
            'list' => 'Cele mai recente întâi',
            'folders' => 'Pe dosare',
        ],
        'empty' => 'Încă nimic. Începe un subiect și îl găsești aici.',
        'all' => 'Toate subiectele',
        'organize' => 'Organizează',
        'guest-title' => 'Păstrează ce înveți',
        'guest-body' => 'Intră în cont și subiectele, harta stăpânirii și coborârile tale te așteaptă și mâine.',
        'guest-cta' => 'Intră în cont',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cum se deschide un strat
    |--------------------------------------------------------------------------
    |
    | Aceeași alegere, fie că e făcută în compozitor sau în sesiune. Fiecare
    | opțiune e numită după ce primești, nu după pedagogia din spate.
    |
    */

    'approach' => [
        'legend' => 'Cum să se deschidă fiecare strat?',
        'next-label' => 'Cum se deschide nivelul următor',
        'guided' => 'Explică-mi întâi',
        'guided-hint' => 'Ghidul predă nivelul, apoi dovedești că l-ai înțeles.',
        'question' => 'Întreabă-mă întâi',
        'question-hint' => 'Verificarea vine direct, fără lecție înainte. Răspunde din ce știi — sau du-te și află.',
        'switched-guided' => 'De la nivelul următor, ghidul predă înainte să întrebe.',
        'switched-question' => 'De la nivelul următor, verificarea vine prima.',
        'posed' => 'Întrebat înainte de a fi predat',
        'teach-cta' => 'Explică-mi nivelul acesta',
        'teach-hint' => 'Adâncimea ta și verificarea deschisă rămân neatinse.',
        'why' => 'De ce funcționează întrebarea pusă înainte?',
    ],

    'auth' => [
        'register-title' => 'Creează-ți contul',
        'register-subtitle' => 'Salvează-ți coborârile, păstrează harta stăpânirii și reia exact din locul în care te-ai oprit.',
        'login-title' => 'Bine ai revenit',
        'login-subtitle' => 'Reia din locul în care ai rămas.',
        'first-name' => 'Prenume',
        'last-name' => 'Nume',
        'first-name-placeholder' => 'Alice',
        'last-name-placeholder' => 'Liddell',
        'email' => 'Email',
        'password' => 'Parolă',
        'password-confirm' => 'Confirmă parola',
        'remember' => 'Ține-mă conectat',
        'register-cta' => 'Creează cont',
        'login-cta' => 'Intră în cont',
        'have-account' => 'Ai deja cont?',
        'no-account' => 'Prima dată aici?',
        'go-login' => 'Intră în cont',
        'go-register' => 'Creează unul',
        'welcome' => 'Bun venit în vizuina iepurelui.',
        'welcome-back' => 'Bine ai revenit.',
        'signed-out' => 'Ai ieșit din cont.',
        'invalid-credentials' => 'Datele acestea nu se potrivesc cu ce avem în evidență.',
        'account-blocked' => 'Acest cont a fost dezactivat.',

        // Panoul de brand (ecrane mari)
        'brand-headline' => 'Coboară. Ieși la suprafață expert.',
        'brand-subline' => 'O coborâre concentrată prin orice subiect — dovedește fiecare nivel  ca să deschizi următorul.',
        'feature-depth' => 'Mergi mai adânc, un nivel  pe rând',
        'feature-prove' => 'Dovedește înainte să avansezi',
        'feature-mastery' => 'Vezi cum se umple harta stăpânirii',

        // OAuth + câmpuri
        'or' => 'sau',
        'continue-with-google' => 'Continuă cu Google',
        'toggle-password' => 'Arată sau ascunde parola',
        'google-failed' => 'Autentificarea cu Google nu s-a finalizat. Încearcă din nou.',
        'google-error' => 'Eroare Google OAuth (cont de învățare): :error',
        'email-placeholder' => 'tu@exemplu.com',
        'email-hint' => 'Trimitem un singur email de confirmare. Fără newslettere.',
        'password-placeholder' => '••••••••',
        'password-hint' => 'Minim 8 caractere, cu o literă și o cifră.',

        // Confirmarea adresei de email
        'verify-title' => 'Confirmă-ți adresa de email',
        'verify-body' => 'Am trimis un link de confirmare la :email. Îți ține contul și progresul pe numele tău — dar poți învăța chiar acum, oricum.',
        'verify-banner' => 'Confirmă :email ca să-ți protejezi contul și coborârea.',
        'verify-resend' => 'Trimite linkul din nou',
        'verify-skip' => 'Continuă să înveți deocamdată',
        'verify-sent' => 'Linkul de confirmare a fost trimis.',
        'verify-done' => 'Email confirmat. Mulțumim.',
        'verify-already' => 'Adresa este deja confirmată.',
        'verify-invalid' => 'Linkul de confirmare nu mai este valid. Cere unul nou.',
    ],

    'mail' => [
        'verify-subject' => 'Confirmă-ți adresa de email · :app',
        'verify-heading' => 'Un clic și ești gata, :name',
        'verify-body' => 'Confirmă-ți adresa de email ca subiectele, harta stăpânirii și coborârea ta să rămână legate de contul tău.',
        'verify-cta' => 'Confirmă adresa de email',
        'verify-expiry' => 'Linkul este valabil :minutes minute.',
        'verify-ignore' => 'Dacă nu ți-ai creat cont, poți ignora liniștit acest mesaj.',
        'sign-off' => 'Ne vedem jos,',
    ],

    'footer' => [
        'tagline' => 'Învață orice, până la capăt.',
        'rights' => 'Toate drepturile rezervate.',
        'blurb' => 'Un instrument de învățare în profunzime, nu un feed. Tu alegi subiectul, ghidul predă un strat, iar tu dovedești că l-ai înțeles înainte să se deschidă următorul.',
        'learn' => 'Învață',
        'how' => 'Cum funcționează',
        'start-descent' => 'Începe o coborâre',
        'your-subjects' => 'Subiectele tale',
        'your-record' => 'Parcursul tău de învățare',
        'methods' => 'Metodele din spate',
        'create-account' => 'Creează un cont',
        'sign-in' => 'Intră în cont',
        'promises' => [
            'no-feed' => 'Fără feed, fără notificări, nimic de derulat.',
            'no-streak-guilt' => 'Fără reproșuri pentru seria pierdută și fără mecanici care mizează pe teama de a pierde.',
            'depth-not-time' => 'Progresul măsoară adâncimea înțeleasă, niciodată timpul petrecut.',
        ],
        'steps' => [
            'name' => 'Alege un subiect pe care vrei să-l înțelegi cu adevărat.',
            'prove' => 'Citește un strat, apoi dovedește cu cuvintele tale că l-ai înțeles.',
            'descend' => 'Treci verificarea și se deschide nivelul următor, dedesubt.',
        ],
    ],

    'home' => [
        'kicker' => 'Începe coborârea',
        'title' => 'Gândește mai adânc',
        'subtitle' => 'Alege un subiect sau lasă un link. Fiecare nivel  îți cere să dovedești că l-ai înțeles înainte să se deschidă următorul — până ieși la suprafață expert.',
        'placeholder' => 'Transformere · Revoluția Franceză · https://o-pagina-pe-care-vrei-sa-o-intelegi.com …',
        'placeholder-label' => 'Subiectul sau linkul pe care vrei să-l înțelegi',
        'composer-hint' => 'Un subiect sau un link de studiat — fără distrageri',
        'cta' => 'Coboară',
        'cta-loading' => 'Coborâm…',
        'resume' => 'Reia',
        'topics-label' => 'Sau intră direct în',
        'settings-label' => 'Cum vrei să te învețe?',
        'mode-legend' => 'Alege un mod de predare',
        'topics' => [
            'Întricarea cuantică',
            'Căderea Romei',
            'Cum funcționează de fapt LLM-urile',
            'Stoicismul',
            'Criza financiară din 2008',
        ],
        'how-title' => 'O singură buclă, repetată până ajungi expert',
        'how-body' => 'Fără bibliotecă de lecții, fără coadă de videoclipuri, fără bancă de teste. O singură conversație cu o adâncime măsurabilă și o poartă la fiecare strat, care se deschide doar pe dovezi.',
        'steps' => [
            'name' => ['title' => 'Alege subiectul', 'body' => 'Orice te poate face curios — sau un link pe care vrei să-l înțelegi cu adevărat. Ghidul pornește de la fundamentul de care are nevoie un începător.'],
            'prove' => ['title' => 'Dovedește nivelul', 'body' => 'Spune-l cu cuvintele tale, aplică-l pe un caz nou sau anticipează un rezultat. Niciodată întrebări de memorie, pe care le-ai putea copia de undeva.'],
            'descend' => ['title' => 'Coboară', 'body' => 'Treci verificarea și se deschide nivelul următor, sub cel de acum. Dacă îți scapă ceva, ghidul corectează înainte să mergi mai departe.'],
        ],
        'features' => [
            'flow' => ['title' => 'Concentrare profundă', 'body' => 'Un singur fir, fără feed, fără zgomot — doar tu și subiectul, tot mai adânc.'],
            'mastery' => ['title' => 'O hartă reală a stăpânirii', 'body' => 'Fiecare concept e marcat ca stăpânit, în formare sau înțeles greșit — din dovezi evaluate, nu din butonul „am înțeles”.'],
            'sources' => ['title' => 'Studiază orice pagină', 'body' => 'Lasă un link și ghidul predă din materialul acela, cu verificări care te țin la ce spune pagina cu adevărat.'],
            'library' => ['title' => 'Biblioteca ta', 'body' => 'Fiecare subiect care te-a făcut curios, așezat în dosarele tale, gata de o nouă coborâre.'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Biblioteca
    |--------------------------------------------------------------------------
    */

    'subjects' => [
        'title' => 'Subiecte',
        'new' => 'Coborâre nouă',
        'select' => 'Selectează',
        'select-all' => 'Selectează tot',
        'selected' => 'selectate',
        'delete' => 'Șterge',
        'deleted' => '{0} Nu s-a șters nimic.|{1} Un subiect șters.|[2,19] :count subiecte șterse.|[20,*] :count de subiecte șterse.',
        'search-label' => 'Caută în subiectele tale',
        'search-placeholder' => 'Caută subiecte…',
        'filter-label' => 'Filtrează subiectele',
        'filter' => [
            'all' => 'Toate',
            'active' => 'În curs',
            'surfaced' => 'La suprafață',
            'shared' => 'Partajate',
        ],
        'load-more' => 'Încarcă mai multe',
        'empty-title' => 'Încă nimic deschis',
        'empty-body' => 'Alege un subiect și primul nivel  te așteaptă. Progresul se salvează de la prima verificare încolo.',
        'no-matches' => 'Nimic nu se potrivește',
        'no-matches-body' => 'Încearcă o căutare mai scurtă sau șterge filtrul.',
        'mastered' => '{1} 1 concept stăpânit|[2,19] :count concepte stăpânite|[20,*] :count de concepte stăpânite',
        'status' => [
            'surfaced' => 'La suprafață',
            'checkpoint' => 'Verificare deschisă',
            'exploring' => 'În curs',
            'shared' => 'Partajat',
        ],

        // Partajare
        'share' => 'Partajează doar pentru citire',
        'unshare' => 'Oprește partajarea',
        'shared-notice' => 'Oricine are acest link poate citi: :url',
        'unshared' => 'Linkul nu mai funcționează. Subiectul este din nou privat.',
        'shared-by' => 'Coborâre semnată de :name',
        'shared-meta' => 'O coborâre în :subject, doar pentru citire — un nivel  pe rând, fiecare dovedit.',
        'shared-cta-title' => 'Să citești nu este să înțelegi.',
        'shared-cta-body' => 'Începe-ți propria coborâre în acest subiect și dovedește fiecare nivel  înainte să se deschidă următorul.',

        // Organizare
        'organization-title' => 'Organizează',
        'organization-subtitle' => 'Rafturile tale. Trage un subiect peste un dosar sau lasă-l în spațiul liber ca să-l scoți din dosar.',
        'tree-label' => 'Dosarele și subiectele tale',
        'tree-search-label' => 'Caută dosare și subiecte',
        'tree-search-placeholder' => 'Filtrează dosare și subiecte…',
        'tree-empty-title' => 'Încă nu ai rafturi',
        'tree-empty-body' => 'Creează un dosar și trage subiectele înăuntru. Dosarele pot ține dosare.',
        'tree-note' => 'Ștergerea unui dosar șterge și dosarele din el, dar păstrează fiecare subiect — se întorc la neclasate.',
        'unfiled' => 'Neclasate',
        'add-folder' => 'Adaugă',
        'folder-name' => 'Numele dosarului',
        'folder-root' => 'Mută la primul nivel',
        'new-subfolder' => 'Dosar nou în acesta',
        'rename' => 'Redenumește',
        'save' => 'Salvează',
        'delete-folder' => 'Șterge dosarul',
        'confirm-delete-folder' => 'Ștergi dosarul?',
        'filed' => 'Mutat.',
        'folder-created' => 'Dosar creat.',
        'folder-saved' => 'Dosar actualizat.',
        'folder-deleted' => 'Dosar șters. Subiectele lui sunt neclasate.',
        'folder-missing' => 'Dosarul acesta nu mai există.',
        'folder-cycle' => 'Un dosar nu poate fi mutat în el însuși.',
        'folder-too-deep' => 'Mai adânc de atât nu merg dosarele. Mută-l mai sus.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Clasamentele
    |--------------------------------------------------------------------------
    |
    | Șase măsuri ale aceluiași registru, fiecare cu trei ferestre de timp.
    | Fiecare etichetă numește ce a fost *dovedit* — nu există clasament pentru
    | timpul petrecut și nici nu va exista.
    |
    */

    'rankings' => [
        'title' => 'Clasamente',
        'subtitle' => 'Clasamentul măsoară ce ai dovedit, nu cât ai stat.',
        'boards-label' => 'Clasamente',
        'period-label' => 'Interval de timp',
        'period' => [
            'all' => 'De la început',
            'month' => 'Luna aceasta',
            'week' => 'Săptămâna aceasta',
        ],

        'board' => [
            'xp' => 'Total XP',
            'xp-hint' => 'Tot ce ai câștigat: straturi încheiate, concepte stăpânite, reușite din prima, subiecte duse până la capăt.',
            'xp-unit' => ':count XP',
            'layers' => 'Straturi încheiate',
            'layers-hint' => 'Fiecare nivel  care a trecut o verificare, din toate subiectele.',
            'layers-unit' => '{1} :count strat|[2,19] :count straturi|[20,*] :count de straturi',
            'concepts' => 'Concepte stăpânite',
            'concepts-hint' => 'Concepte demonstrate de mai multe ori — dovedite, nu declarate.',
            'concepts-unit' => '{1} :count concept|[2,19] :count concepte|[20,*] :count de concepte',
            'depth' => 'Cea mai adâncă coborâre',
            'depth-hint' => 'Cele mai multe straturi dovedite într-un singur subiect.',
            'depth-unit' => '{1} :count strat|[2,19] :count straturi|[20,*] :count de straturi',
            'subjects' => 'Subiecte începute',
            'subjects-hint' => 'Subiecte cu cel puțin un nivel  încheiat. Să deschizi unul nu costă nimic; aici se numără cele în care chiar ai intrat.',
            'subjects-unit' => '{1} :count subiect|[2,19] :count subiecte|[20,*] :count de subiecte',
            'surfaced' => 'Subiecte duse până la capăt',
            'surfaced-hint' => 'Coborâri duse până jos și înapoi. Cel mai rar lucru de aici.',
            'surfaced-unit' => '{1} :count subiect|[2,19] :count subiecte|[20,*] :count de subiecte',
        ],

        'rank' => 'Loc',
        'learner' => 'Cursant',
        'result' => 'Rezultat',
        'you' => 'Tu',
        'podium' => 'Primii trei',
        'participants' => '{0} Încă nimeni în clasamente|{1} 1 cursant în clasamente|[2,19] :count cursanți în clasamente|[20,*] :count de cursanți în clasamente',
        'view-profile' => 'Vezi ce a dovedit :name',
        'empty-title' => 'Încă nimic în acest clasament',
        'empty-body' => 'Se umple pe măsură ce cursanții dovedesc straturi. Încheie unul și primul nume de aici ar putea fi al tău.',

        'your-standing' => 'Unde ești',
        'unranked-value' => 'Încă nimic aici',
        'unranked-hint' => 'Încheie un nivel  și intri în acest clasament.',
        'hidden-hint' => 'Doar tu vezi asta — nu ești în clasamente.',
        'gap' => ':value până la locul următor',
        'leading' => 'Nimeni deasupra ta pe acest clasament.',
        'best-standings' => 'Cele mai bune poziții',
        'no-standings' => 'Încă în niciun clasament.',

        'join-title' => 'Intră în clasamente',
        'join-body' => 'Dacă intri, numele, poza și parcursul tău devin vizibile celorlalți cursanți. Nimic altceva nu se schimbă și poți ieși oricând.',
        'join-cta' => 'Intră în clasamente',
        'leave-title' => 'Ești în clasamente',
        'leave-body' => 'Ceilalți cursanți îți văd numele și parcursul de învățare și îți pot deschide pagina din orice clasament.',
        'leave-cta' => 'Ieși din clasamente',
        'joined' => 'Ești în clasamente. Parcursul tău este vizibil celorlalți cursanți.',
        'left' => 'Ai ieșit din clasamente. Parcursul tău este din nou privat.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Un cursant, așa cum îl văd ceilalți
    |--------------------------------------------------------------------------
    |
    | Numele oamenilor nu intră niciodată la genitiv aici: „evidența lui Maria”
    | e greșit, iar „Parcursul lui/ei” e o soluție de traducător. Titlurile sunt
    | construite ca propoziții — „Ce a dovedit :name” — și merg pentru oricine.
    |
    */

    'learners' => [
        'title' => 'Ce a dovedit :name',
        'meta' => 'Ce a dovedit :name pe Down the Rabbit Hole — straturi încheiate, concepte stăpânite, subiecte duse până la capăt.',
        'since' => 'Coboară aici din :date',
        'preview-title' => 'Pagina aceasta o vezi doar tu',
        'preview-body' => 'Exact asta ar vedea ceilalți cursanți dacă ai intra în clasamente.',
        'shared-title' => 'Coborâri publicate',
        'shared-body' => 'Subiecte pe care :name a ales să le lase la vedere.',
        'shared-empty' => ':name nu a publicat încă nicio coborâre.',
        'back-to-rankings' => 'Înapoi la clasamente',
    ],

    'profile' => [
        'title' => 'Contul tău',
        'tabs-label' => 'Secțiunile contului',
        'tab-record' => 'Parcurs',
        'tab-profile' => 'Detalii',
        'tab-password' => 'Parolă',
        'change-photo' => 'Schimbă poza',
        'xp' => ':xp XP',
        'details' => 'Detaliile tale',
        'details-hint' => 'Folosite în contul tău și în emailurile pe care ți le trimitem.',
        'phone' => 'Număr de telefon',
        'email-change-hint' => 'Dacă schimbi adresa, va trebui să o confirmi pe cea nouă.',
        'save' => 'Salvează modificările',
        'change-password' => 'Schimbă parola',
        'change-password-hint' => 'Folosește o parolă puternică, pe care nu o mai folosești nicăieri.',
        'current-password' => 'Parola actuală',
        'new-password' => 'Parola nouă',
        'update-password' => 'Actualizează parola',
        'updated' => 'Detaliile tale au fost salvate.',
        'password-updated' => 'Parola ta a fost schimbată.',
        'password-incorrect' => 'Parola actuală este greșită.',
        'avatar-updated' => 'Poza ta a fost actualizată.',
        'error' => 'Ceva nu a mers. Încearcă din nou.',
        'record' => [
            'layers' => 'Straturi încheiate',
            'layers-hint' => 'Fiecare a trecut o verificare.',
            'mastered' => 'Concepte stăpânite',
            'mastered-hint' => 'Demonstrate de mai multe ori.',
            'deepest' => 'Cea mai adâncă coborâre',
            'deepest-hint' => 'Straturi dovedite într-un singur subiect.',
            'deepest-dive' => 'Cea mai adâncă coborâre',
            'deepest-dive-value' => '{1} :subject — un strat|[2,19] :subject — :count straturi|[20,*] :subject — :count de straturi',
            'subjects' => 'Subiecte',
            'surfaced' => 'Încheiate',
            'to-review' => 'De reluat',
            'to-review-hint' => 'Concepte încă înțelese greșit.',
            'note' => 'Acestea sunt singurele cifre pe care le păstrăm. Nu există total de timp petrecut și nimic de aici nu te răsplătește pentru că ai deschis aplicația fără să înveți ceva. Clasamentele măsoară exact aceleași cifre, și doar dacă le ceri tu.',
        ],
    ],

    'chat' => [
        // Mesaje de stare / erori
        'no-access' => 'Subiectul acesta nu este al tău.',
        'no-checkpoint' => 'Nu există nicio verificare deschisă pe acest strat.',
        'daily-limit' => 'Ai atins limita de coborâri pentru azi. Revino mâine — odihna face parte din lucrul în profunzime.',
        'already-surfaced' => 'Din acesta ai ieșit deja la suprafață.',
        'llm-error' => 'Ghidul a pierdut firul o clipă. Mai încearcă.',
        'grade-unavailable' => 'Ghidul nu a putut citi răspunsul tău acum — nu se pierde nimic, trimite-l din nou.',
        'connection-lost' => 'Conexiunea s-a întrerupt. Reîncarcă pagina ca să continui de unde erai.',

        // Studiul unei pagini
        'source-unreachable' => 'Linkul acesta nu poate fi accesat de aici. Verifică-l sau alege subiectul direct.',
        'source-failed' => 'Pagina nu s-a încărcat. Încearcă alt link sau alege subiectul direct.',
        'source-too-thin' => 'Pagina nu are destul text lizibil pentru o coborâre.',

        // Subiectul
        'subject-label' => 'Coborâm în',
        'depth-reached' => 'Adâncime atinsă: nivelul :depth din :max',
        'layer' => 'nivelul',
        'depth-rail' => 'Coborârea',
        'mastery' => 'Stăpânire',
        'concepts' => 'Concepte',
        'concepts-empty' => 'Conceptele apar aici pe măsură ce le dovedești — starea fiecăruia vine din ce ai demonstrat, nu din ce ai citit.',
        'thinking' => 'Se gândește',
        'thought-for' => 'Gândit până la capăt',
        'analyzing' => 'Îți cântărește răspunsul față de nivelul acesta…',
        'begin' => 'Deschide primul strat',
        // Pasul Survey din SQ3R. „Recunoaștere” pentru că e ce faci înainte
        // să intri pe un teren pe care nu-l cunoști — și pentru că „hartă”
        // e deja luat de harta stăpânirii.
        'survey' => 'Recunoaștere',
        'survey-cta' => 'Recunoaște terenul întâi',
        'survey-hint' => 'O hartă a terenului înainte să intri în el. Nu costă adâncime și nu dovedește nimic.',
        'go-deeper' => 'Mai adânc',
        'next-layer' => 'nivelul :depth se deschide sub acesta',
        'focus-toggle' => 'Mod concentrare',
        'focus-on' => 'Mod concentrare activat',
        'focus-off' => 'Mod concentrare oprit',
        'stop' => 'Oprește',
        'stopped' => 'Oprit. Ce a ajuns rămâne.',
        'copy' => 'Copiază nivelul',
        'copied' => 'Copiat',

        // Ce face ghidul, înainte să existe text de arătat
        'stage' => [
            'orienting' => 'Se orientează în „:subject”',
            'reading' => 'Citește :title — :words de cuvinte',
            'recalling' => '{1} Își amintește un concept dovedit de tine|[2,19] Își amintește :count concepte dovedite de tine|[20,*] Își amintește :count de concepte dovedite de tine',
            'revisiting' => '{1} Reia un concept de revizuit|[2,19] Reia :count concepte de revizuit|[20,*] Reia :count de concepte de revizuit',
            'composing-teach' => 'Compune nivelul :layer',
            'composing-question' => 'Pregătește verificarea pentru nivelul :layer',
            'composing-survey' => 'Cartografiază terenul',
            'writing' => 'Scrie nivelul',
        ],

        // Verificarea
        'checkpoint' => 'Dovedește că ai înțeles',
        'checkpoint-ready' => 'O verificare îți așteaptă răspunsul.',
        'proof-placeholder' => 'Explică-mi cu cuvintele tale…',
        'submit-proof' => 'Dovedește',
        'your-answer' => 'Răspunsul tău',
        'their-answer' => 'Răspunsul lor',
        'confidence-legend' => 'Câtă încredere ai?',
        'confidence' => [
            'shaky' => 'Ghicesc',
            'mostly' => 'Aproape',
            'solid' => 'Știu sigur',
        ],
        'stuck' => 'Te-ai blocat? Ia-o pe alt drum',
        'reframe' => [
            'different' => 'Explică altfel',
            'analogy' => 'Dă-mi o analogie',
            'evidence' => 'Arată dovezile',
            'challenge' => 'Provoacă-mă',
        ],

        // Verdictul
        'verdict-pass' => 'nivel  încheiat',
        'verdict-incomplete' => 'Dovadă incompletă',
        'verdict-misconception' => 'O confuzie de limpezit',
        'verdict-toggle' => 'Arată sau ascunde detaliile verdictului',
        'score' => 'Punctaj',
        'criterion-met' => 'demonstrat',
        'criterion-unmet' => 'încă nedemonstrat',
        'you-thought' => 'Din răspuns reiese că ai crezut că:',
        'calibration-good' => 'Calibrare bună — știai ce știi',
        'calibration-over' => 'Ai avut mai multă încredere decât susține răspunsul — merită o a doua privire',
        'calibration-under' => 'Știai mai mult decât credeai',
        'resurfaced' => 'Readus din adâncimea :depth',
        'resurfaced-short' => 'din :depth',

        // Stări
        'layer-state' => [
            'cleared' => 'încheiat',
            'current' => 'nivelul actual',
            'review' => 'încheiat, ceva de revizuit',
            'locked' => 'încă nedeschis',
        ],
        'concept-state' => [
            'mastered' => 'Stăpânit',
            'developing' => 'În formare',
            'misunderstood' => 'Înțeles greșit',
            'unexplored' => 'Neexplorat',
        ],

        // La suprafață
        'surfaced-title' => 'Ai ieșit la suprafață expert.',
        'surfaced-body' => 'Ai coborât până jos și te-ai întors sus, dovedind fiecare nivel  pe drum. Exact asta înseamnă să înțelegi ceva până la capăt.',
        'new-descent' => 'Începe altă coborâre',
    ],

    /*
    |--------------------------------------------------------------------------
    | Metodele
    |--------------------------------------------------------------------------
    |
    | Numele și rezumatul fiecărei metode se traduc aici, pentru că indexul se
    | randează fără niciun apel la model. Textul lung al fiecărei intrări este
    | scris de ghid, o dată pentru fiecare limbă, și pănivel  în baza de date;
    | bibliografia de dedesubt este verificată de om și stă în config/platform.php.
    |
    */

    'methods' => [
        'title' => 'Metodele',
        'subtitle' => 'Fiecare mecanism de aici vine de undeva. Ce este fiecare tehnică, când funcționează, unde dovezile sunt subțiri — și unde o întâlnești în aplicație.',
        'meta' => 'Tehnicile de învățare din spatele Down the Rabbit Hole — ce este fiecare, când funcționează și ce cercetare o susține.',
        'entry-meta' => ':name — ce este, când funcționează și ce dovezi există.',
        'link' => 'Ce este :name?',
        'back' => 'Toate metodele',
        'read' => 'Citește intrarea',
        'references' => 'Pentru studiu suplimentar',
        'references-note' => 'Surse verificate de om. Textul de mai sus este scris pentru tine în limba ta; lista aceasta nu este generată.',
        'unavailable' => 'Intrarea aceasta încă se scrie. Revino peste puțin — bibliografia de mai jos este deja aici.',
        'cta-title' => 'Să citești despre o metodă nu înseamnă să o folosești.',
        'cta-body' => 'Alege un subiect și pornește bucla: un strat, o verificare, un lucru dovedit.',
        'cta' => 'Începe o coborâre',

        // Perechile, nu doar cuvintele: ghidul primește textul în engleză și
        // trebuie să știe care termen românesc îi corespunde fiecăruia.
        'glossary' => 'subject = subiect, layer = strat, descent = coborâre, checkpoint = verificare, prove it = dovedește, mastery = stăpânire, surfaced = la suprafață, guide = ghid, learner = cursant, survey = recunoaștere',

        'items' => [
            'sq3r' => [
                'name' => 'SQ3R',
                'summary' => 'Survey, Question, Read, Recite, Review — cinci pași care transformă cititul unui text în răspunsul la el.',
            ],
            'retrieval-practice' => [
                'name' => 'Practica reactualizării',
                'summary' => 'Scoți ideea din memorie în loc să o bagi din nou înăuntru. Efortul de a-ți aminti este cel care o fixează.',
            ],
            'spaced-repetition' => [
                'name' => 'Practica distribuită',
                'summary' => 'Aceleași ore, împrăștiate în loc de îngrămădite. Faptul că uiți puțin între sesiuni este mecanismul, nu eșecul.',
            ],
            'desirable-difficulties' => [
                'name' => 'Dificultățile utile',
                'summary' => 'Condiții care încetinesc învățarea în timp ce se petrece și îmbunătățesc ce rămâne din ea. Studiul ușor se simte mai bine și funcționează mai prost.',
            ],
            'self-explanation' => [
                'name' => 'Autoexplicarea',
                'summary' => 'Îți spui ție de ce — de ce urmează pasul acesta, de ce răspunsul celălalt e greșit. Scoate la iveală golurile pe care recitirea le acoperă.',
            ],
            'interleaving' => [
                'name' => 'Intercalarea',
                'summary' => 'Amesteci probleme înrudite în loc să exersezi un singur tip la rând, ca să fii nevoit să alegi metoda, nu doar să o aplici.',
            ],
            'calibration' => [
                'name' => 'Calibrarea',
                'summary' => 'Să știi ce știi. Distanța dintre cât de sigur te-ai simțit și cât de corect ai fost se antrenează — și ea îți spune când să te oprești din învățat.',
            ],
            'learning-by-teaching' => [
                'name' => 'Învățarea prin predare',
                'summary' => 'Explici ideea în cuvinte simple cuiva care nu o are. Ce nu reușești să spui este exact ce nu ai.',
            ],
            'socratic' => [
                'name' => 'Metoda socratică',
                'summary' => 'Ești întrebat în loc să fii informat, o întrebare pe rând, până când răspunsul e al tău și nu memorat.',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Modurile de predare
    |--------------------------------------------------------------------------
    |
    | Rândurile sunt administrate din panoul de admin, așa că `lang/en` NU are
    | acest bloc: engleza urmează baza de date, iar traducerile de aici se
    | potrivesc după `slug`. Un mod adăugat din panou apare în engleză până
    | primește o intrare aici — niciodată ca o cheie nerezolvată.
    | Vezi App\Models\LearningMode::label().
    |
    */

    'modes' => [
        'socratic' => [
            'name' => 'Socratic',
            'tagline' => 'Întrebări înaintea răspunsurilor.',
        ],
        'visual-explanation' => [
            'name' => 'Explicație vizuală',
            'tagline' => 'Diagrame, structură, intuiție spațială.',
        ],
        'exam-preparation' => [
            'name' => 'Pregătire pentru examen',
            'tagline' => 'Precizie, terminologie, capcane frecvente.',
        ],
        'project-based' => [
            'name' => 'Pe proiect',
            'tagline' => 'Construiește ceva cu fiecare strat.',
        ],
        'fast-overview' => [
            'name' => 'Privire de ansamblu',
            'tagline' => 'Forma domeniului, repede.',
        ],
        'deep-technical-descent' => [
            'name' => 'Coborâre tehnică adâncă',
            'tagline' => 'Mecanisme, cazuri limită, principii de bază.',
        ],
    ],
];
