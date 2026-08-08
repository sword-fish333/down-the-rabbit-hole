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
|   subject → subiect · layer → strat · descent → coborâre · descend → coboară
|   checkpoint → verificare · prove it → dovedește · mastery → stăpânire
|   surfaced → la suprafață · folder → dosar · streak → serie
|
| Plurals carry all three Romanian forms — 1 / 2–19 / 20 „de” — because
| ":count de straturi" is what a Romanian reads and "20 straturi" is not.
|
*/

return [

    'meta' => [
        'description' => 'Alege un subiect — sau lasă un link — și coboară. Fiecare strat îți cere să dovedești că l-ai înțeles înainte să se deschidă următorul, până ieși la suprafață expert.',
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
        'theme-toggle' => 'Comută luminos / întunecat',
        'sign-in' => 'Intră în cont',
        'start' => 'Începe o coborâre',
        'sign-out' => 'Ieși din cont',
        'subjects' => 'Subiecte',
        'profile' => 'Profil',
        'account' => 'Meniul contului',
        'streak-title' => 'Zile consecutive cu un strat încheiat',
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
        'empty' => 'Încă nimic. Alege un subiect și te așteaptă aici.',
        'all' => 'Toate subiectele',
        'organize' => 'Organizează',
        'guest-title' => 'Păstrează ce înveți',
        'guest-body' => 'Intră în cont și subiectele, harta stăpânirii și coborârea ta sunt aici și mâine.',
        'guest-cta' => 'Intră în cont',
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
        'brand-subline' => 'O coborâre concentrată prin orice subiect — dovedește fiecare strat ca să deschizi următorul.',
        'feature-depth' => 'Mergi mai adânc, un strat pe rând',
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
        'your-record' => 'Evidența ta de învățare',
        'create-account' => 'Creează un cont',
        'sign-in' => 'Intră în cont',
        'promises' => [
            'no-feed' => 'Fără feed, fără notificări, nimic de derulat.',
            'no-streak-guilt' => 'Fără vinovăție pentru serii pierdute și fără mecanici de anxietate.',
            'depth-not-time' => 'Progresul măsoară adâncimea înțeleasă, niciodată timpul petrecut.',
        ],
        'steps' => [
            'name' => 'Alege un subiect pe care vrei să-l înțelegi cu adevărat.',
            'prove' => 'Citește un strat, apoi dovedește cu vorbele tale că l-ai prins.',
            'descend' => 'Treci verificarea și se deschide stratul următor, dedesubt.',
        ],
    ],

    'home' => [
        'kicker' => 'Începe coborârea',
        'title' => 'Dovedește ce știi',
        'subtitle' => 'Alege un subiect sau pune un link. Fiecare strat îți cere să dovedești că l-ai prins înainte să se deschidă următorul — până ieși la suprafață expert.',
        'placeholder' => 'Transformere · Revoluția Franceză · https://o-pagina-pe-care-vrei-sa-o-intelegi.com …',
        'placeholder-label' => 'Subiectul sau linkul pe care vrei să-l înțelegi',
        'composer-hint' => 'Un subiect sau un link de studiat — fără distrageri',
        'cta' => 'Coboară',
        'cta-loading' => 'Coborâm…',
        'resume' => 'Reia',
        'topics-label' => 'Sau intră direct în',
        'mode-label' => 'Cum să te învețe?',
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
            'prove' => ['title' => 'Dovedește stratul', 'body' => 'Explică-l înapoi, aplică-l pe un caz nou sau anticipează un rezultat. Niciodată întrebări de memorie pe care le-ai putea copia.'],
            'descend' => ['title' => 'Coboară', 'body' => 'Treci verificarea și se deschide stratul următor, sub cel de acum. Dacă îți scapă ceva, ghidul corectează înainte să mergi mai departe.'],
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
        'empty-body' => 'Alege un subiect și primul strat te așteaptă. Progresul se salvează de la prima verificare încolo.',
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
        'shared-by' => 'Coborâre de :name',
        'shared-meta' => 'O coborâre în :subject, doar pentru citire — un strat pe rând, fiecare dovedit.',
        'shared-cta-title' => 'Să citești nu este să înțelegi.',
        'shared-cta-body' => 'Începe-ți propria coborâre în acest subiect și dovedește fiecare strat înainte să se deschidă următorul.',

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
        'folder-root' => 'Mută la nivelul principal',
        'folder-inside' => 'În :name',
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
        'move-failed' => 'Mutarea nu a rămas. Nu s-a schimbat nimic.',
    ],

    'profile' => [
        'title' => 'Contul tău',
        'tabs-label' => 'Secțiunile contului',
        'tab-record' => 'Evidență',
        'tab-profile' => 'Detalii',
        'tab-password' => 'Parolă',
        'change-photo' => 'Schimbă poza',
        'xp' => ':xp XP',
        'streak' => '{1} serie de o zi|[2,19] serie de :count zile|[20,*] serie de :count de zile',
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
            'deepest' => 'Cel mai adânc strat',
            'deepest-hint' => 'Cea mai adâncă coborâre a ta.',
            'deepest-dive' => 'Cea mai adâncă coborâre',
            'deepest-dive-value' => '{1} :subject — un strat|[2,19] :subject — :count straturi|[20,*] :subject — :count de straturi',
            'subjects' => 'Subiecte',
            'surfaced' => 'Încheiate',
            'to-review' => 'De reluat',
            'to-review-hint' => 'Concepte încă înțelese greșit.',
            'note' => 'Acestea sunt singurele cifre pe care le păstrăm. Nu există clasament, nu există total de timp petrecut și nimic de aici nu te răsplătește pentru că ai deschis aplicația fără să înveți ceva.',
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
        'depth' => 'Adâncime',
        'depth-reached' => 'Adâncime atinsă: stratul :depth din :max',
        'layer' => 'Stratul',
        'of' => 'din',
        'depth-rail' => 'Coborârea',
        'mastery' => 'Stăpânire',
        'concepts' => 'Concepte',
        'concepts-empty' => 'Conceptele apar aici pe măsură ce le dovedești, marcate după ce ai demonstrat cu adevărat.',
        'thinking' => 'Gândește',
        'thought-for' => 'A gândit până la capăt',
        'analyzing' => 'Îți citește răspunsul în raport cu stratul…',
        'begin' => 'Deschide primul strat',
        'go-deeper' => 'Mai adânc',
        'next-layer' => 'Stratul :depth se deschide sub acesta',
        'focus-toggle' => 'Mod concentrare',
        'focus-on' => 'Mod concentrare activat',
        'focus-off' => 'Mod concentrare oprit',
        'stop' => 'Oprește',
        'stopped' => 'Oprit. Ce a ajuns rămâne.',
        'copy' => 'Copiază stratul',
        'copied' => 'Copiat',

        // Ce face ghidul, înainte să existe text de arătat
        'stage' => [
            'orienting' => 'Se orientează în „:subject”',
            'reading' => 'Citește :title — :words de cuvinte',
            'recalling' => '{1} Își amintește un concept dovedit de tine|[2,19] Își amintește :count concepte dovedite de tine|[20,*] Își amintește :count de concepte dovedite de tine',
            'revisiting' => '{1} Reia un concept de revizuit|[2,19] Reia :count concepte de revizuit|[20,*] Reia :count de concepte de revizuit',
            'composing' => 'Compune stratul :layer',
            'writing' => 'Îl scrie',
        ],

        // Verificarea
        'checkpoint' => 'Dovedește că ai înțeles',
        'checkpoint-ready' => 'O verificare îți așteaptă răspunsul.',
        'proof-placeholder' => 'Explică-l înapoi cu vorbele tale…',
        'submit-proof' => 'Dovedește',
        'your-answer' => 'Răspunsul tău',
        'their-answer' => 'Răspunsul lor',
        'confidence-legend' => 'Cât de sigur ești?',
        'confidence' => [
            'shaky' => 'Nesigur',
            'mostly' => 'Aproape',
            'solid' => 'Solid',
        ],
        'stuck' => 'Te-ai blocat? Încearcă alt unghi',
        'reframe' => [
            'different' => 'Explică altfel',
            'analogy' => 'Dă-mi o analogie',
            'evidence' => 'Arată dovezile',
            'challenge' => 'Provoacă-mă',
        ],

        // Verdictul
        'verdict-pass' => 'Strat încheiat',
        'verdict-incomplete' => 'Încă nu e chiar acolo',
        'verdict-misconception' => 'Ceva de corectat mai întâi',
        'verdict-toggle' => 'Arată sau ascunde detaliile verdictului',
        'score' => 'Punctaj',
        'criterion-met' => 'demonstrat',
        'criterion-unmet' => 'încă nedemonstrat',
        'you-thought' => 'Ai părut să crezi că:',
        'calibration-good' => 'Bine calibrat — știai ce știi',
        'calibration-over' => 'Te-ai simțit mai sigur decât a arătat răspunsul — merită o a doua privire',
        'calibration-under' => 'Știai mai mult decât ți-ai acordat',
        'resurfaced' => 'Readus din adâncimea :depth',
        'resurfaced-short' => 'din :depth',

        // Stări
        'layer-state' => [
            'cleared' => 'încheiat',
            'current' => 'stratul actual',
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
        'surfaced-body' => 'Ai coborât până jos și te-ai întors sus, dovedind fiecare strat pe drum. Ăsta e tot jocul.',
        'new-descent' => 'Începe altă coborâre',
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
