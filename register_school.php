<?php
require_once __DIR__ . '/security.php';
app_start_session();
$csrf = csrf_token();
?>
<!doctype html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Permohonan langganan Digital Registration System untuk sekolah.">
    <title>Permohonan Sekolah | DRS</title>
    <link rel="icon" href="images/default_logo.png" type="image/png">
    <style>
        :root{--navy:#172d42;--navy-soft:#213e58;--green:#0ba574;--green-dark:#07825a;--green-pale:#eaf9f3;--orange:#f39a31;--ink:#18232e;--muted:#687888;--line:#dce5ea;--surface:#f7f9fa;--danger:#c93847;--shadow:0 30px 80px rgba(22,45,66,.18)}
        *{box-sizing:border-box}html{font-size:16px}body{margin:0;background:#edf3f4;color:var(--ink);font-family:Inter,ui-sans-serif,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;line-height:1.5}button,input{font:inherit}[hidden]{display:none!important}
        .page{min-height:100vh;display:grid;place-items:center;padding:34px;background:radial-gradient(circle at 5% 8%,rgba(11,165,116,.11),transparent 29%),radial-gradient(circle at 96% 90%,rgba(243,154,49,.1),transparent 25%),#edf3f4}
        .shell{width:min(1280px,100%);min-height:760px;display:grid;grid-template-columns:370px minmax(0,1fr);overflow:hidden;border:1px solid rgba(23,45,66,.08);border-radius:28px;background:#fff;box-shadow:var(--shadow)}
        .side{position:relative;display:flex;flex-direction:column;padding:42px 40px;color:#fff;background:linear-gradient(155deg,#142a3f 0%,#1c3b56 100%);overflow:hidden}.side:before{content:"";position:absolute;width:360px;height:360px;right:-225px;top:-150px;border:1px solid rgba(255,255,255,.09);border-radius:50%}.side:after{content:"";position:absolute;width:270px;height:270px;left:-190px;bottom:-125px;border:1px solid rgba(255,255,255,.07);border-radius:50%}
        .logo{position:relative;z-index:1;width:218px;height:78px;object-fit:contain;object-position:left center}.side-copy{position:relative;z-index:1;margin-top:58px}.eyebrow{margin:0 0 15px;color:#86e9c5;font-size:.7rem;font-weight:900;letter-spacing:.15em;text-transform:uppercase}.side h1{max-width:300px;margin:0;font-size:2.05rem;line-height:1.16;letter-spacing:-.043em}.side h1 span{display:block}.side h1 span+span{color:#94e7c8}.side-lead{max-width:292px;margin:19px 0 0;color:#cfdae3;font-size:.92rem;line-height:1.7}
        .highlights{position:relative;z-index:1;margin-top:44px;padding-top:22px;border-top:1px solid rgba(255,255,255,.13)}.highlights-title{margin:0 0 13px;color:#91a8b9;font-size:.67rem;font-weight:850;letter-spacing:.12em;text-transform:uppercase}.highlights ul{list-style:none;margin:0;padding:0}.highlights li{position:relative;display:flex;align-items:center;min-height:39px;padding-left:31px;color:#edf5f8;font-size:.79rem;font-weight:750}.highlights li:before{content:"✓";position:absolute;left:0;width:20px;height:20px;display:grid;place-items:center;border-radius:50%;background:rgba(134,233,197,.14);color:#8ee9c7;font-size:.68rem;font-weight:900}.highlights li+li{border-top:1px solid rgba(255,255,255,.07)}
        .privacy{position:relative;z-index:1;margin-top:auto;padding:17px 18px;border:1px solid rgba(255,255,255,.1);border-radius:13px;background:rgba(7,23,36,.18);display:grid;grid-template-columns:30px 1fr;gap:11px;color:#bfcdd7;font-size:.71rem;line-height:1.5}.privacy-mark{width:30px;height:30px;display:grid;place-items:center;border-radius:9px;background:rgba(134,233,197,.12);color:#8be8c5}.privacy strong{display:block;margin-bottom:2px;color:#fff;font-size:.76rem}
        .main{display:flex;min-width:0;flex-direction:column;padding:40px 52px 36px}.topbar{display:flex;align-items:center;justify-content:space-between;gap:20px}.application-tag{display:inline-flex;align-items:center;gap:8px;color:var(--green-dark);font-size:.74rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase}.application-tag:before{content:"";width:8px;height:8px;border-radius:50%;background:var(--green)}.login-link{color:#5d6d7b;font-size:.8rem;font-weight:700;text-decoration:none}.login-link:hover{color:var(--navy)}
        .heading{margin:24px 0 26px}.heading h2{margin:0;font-size:1.9rem;letter-spacing:-.035em}.heading p{margin:7px 0 0;color:var(--muted);font-size:.91rem}
        .progress{display:grid;grid-template-columns:repeat(3,1fr);margin-bottom:30px}.progress-step{position:relative;display:flex;align-items:center;gap:10px;padding:0 14px 15px 0;border:0;border-bottom:3px solid #e6ecef;background:transparent;color:#8996a1;text-align:left;cursor:default}.progress-step:not(:last-child){margin-right:12px}.progress-no{width:28px;height:28px;display:grid;place-items:center;flex:0 0 auto;border-radius:9px;background:#edf1f3;color:#7a8996;font-size:.74rem;font-weight:850}.progress-label{font-size:.78rem;font-weight:800;line-height:1.25}.progress-step.active{border-color:var(--green);color:var(--ink)}.progress-step.active .progress-no{background:var(--green);color:#fff}.progress-step.done{border-color:#8dd6bc;color:#486255;cursor:pointer}.progress-step.done .progress-no{background:var(--green-pale);color:var(--green-dark)}
        .alert{display:none;margin:-8px 0 20px;padding:12px 14px;border-radius:10px;font-size:.82rem}.alert.show{display:block}.alert.error{border:1px solid #fecdd3;background:#fff1f2;color:#9f2633}.alert.success{border:1px solid #a7f3d0;background:#ecfdf5;color:#087a54}
        form{display:flex;min-height:0;flex:1;flex-direction:column}.stage{animation:stage-in .24s ease}.stage-head{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:24px}.stage-kicker{margin:0 0 5px;color:var(--green-dark);font-size:.71rem;font-weight:850;letter-spacing:.1em;text-transform:uppercase}.stage h3{margin:0;font-size:1.23rem;letter-spacing:-.015em}.stage-description{margin:5px 0 0;color:var(--muted);font-size:.82rem}.required-note{padding-top:6px;color:var(--muted);font-size:.72rem;white-space:nowrap}.required{color:var(--danger)}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:21px 24px}.field label{display:block;margin-bottom:8px;color:#2b3946;font-size:.8rem;font-weight:800}.input{width:100%;height:54px;padding:0 16px;border:1px solid var(--line);border-radius:11px;outline:0;background:#fbfcfd;color:var(--ink);font-size:.91rem;transition:.18s}.input::placeholder{color:#94a2ad}.input:hover{border-color:#b6c5ce}.input:focus{border-color:var(--green);background:#fff;box-shadow:0 0 0 4px rgba(11,165,116,.1)}.hint{margin:7px 0 0;color:var(--muted);font-size:.72rem}.password-wrap{position:relative}.password-wrap .input{padding-right:70px}.toggle-password{position:absolute;right:8px;top:9px;height:36px;padding:0 10px;border:0;border-radius:8px;background:transparent;color:var(--green-dark);font-size:.72rem;font-weight:850;cursor:pointer}.toggle-password:hover{background:var(--green-pale)}
        .plans{display:grid;grid-template-columns:1fr 1fr;gap:20px}.plan{position:relative;display:flex;min-height:270px;flex-direction:column;padding:25px;border:1.5px solid var(--line);border-radius:17px;background:#fff;cursor:pointer;transition:.2s}.plan:hover{transform:translateY(-2px);border-color:#91ccb8;box-shadow:0 15px 30px rgba(23,45,66,.08)}.plan.selected{border-color:var(--green);background:#f3fcf8;box-shadow:0 0 0 3px rgba(11,165,116,.09)}.plan input{position:absolute;opacity:0}.plan-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}.plan-name{font-size:1.05rem;font-weight:900}.plan-caption{display:block;margin-top:2px;color:var(--muted);font-size:.72rem}.radio{width:21px;height:21px;display:grid;place-items:center;border:1.5px solid #a9b7c1;border-radius:50%}.selected .radio{border-color:var(--green)}.selected .radio:after{content:"";width:10px;height:10px;border-radius:50%;background:var(--green)}.price{margin:24px 0 18px;color:var(--ink);font-size:2rem;font-weight:900;letter-spacing:-.04em}.price sup{font-size:.76rem;vertical-align:top;line-height:2}.price span{color:var(--muted);font-size:.72rem;font-weight:650;letter-spacing:0}.plan ul{list-style:none;margin:auto 0 0;padding:17px 0 0;border-top:1px solid #dfe8e4;color:#506170;font-size:.78rem}.plan li{position:relative;margin:9px 0;padding-left:20px}.plan li:before{content:"✓";position:absolute;left:0;color:var(--green);font-weight:900}.popular{position:absolute;top:-11px;right:17px;padding:4px 10px;border-radius:999px;background:var(--orange);color:#fff;font-size:.63rem;font-weight:900;letter-spacing:.05em}.plan-note{display:flex;gap:9px;margin:15px 0 0;padding:12px 14px;border-radius:10px;background:var(--surface);color:#61717f;font-size:.73rem}.plan-note strong{color:var(--navy)}
        .review{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;padding:16px;border:1px solid #cce9dd;border-radius:13px;background:var(--green-pale)}.review-item span{display:block;color:#5d776d;font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em}.review-item strong{display:block;margin-top:3px;color:#173c2f;font-size:.86rem}.consent{display:flex;align-items:flex-start;gap:10px;margin-top:20px;padding:14px 15px;border:1px solid var(--line);border-radius:11px;background:var(--surface);color:#526270;font-size:.75rem;line-height:1.5}.consent input{width:17px;height:17px;flex:0 0 auto;margin:2px 0 0;accent-color:var(--green)}
        .actions{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-top:auto;padding-top:28px}.actions-right{display:flex;gap:11px;margin-left:auto}.btn{height:51px;padding:0 22px;border-radius:11px;font-size:.82rem;font-weight:850;cursor:pointer;transition:.18s}.btn-secondary{border:1px solid var(--line);background:#fff;color:#536471}.btn-secondary:hover{border-color:#aebec8;background:var(--surface)}.btn-primary{min-width:180px;border:0;background:var(--green);color:#fff;box-shadow:0 10px 22px rgba(11,165,116,.2)}.btn-primary:hover{transform:translateY(-1px);background:var(--green-dark)}.btn:disabled{opacity:.65;cursor:wait;transform:none}.step-count{color:#8794a0;font-size:.73rem}.spinner{display:inline-block;width:14px;height:14px;margin-right:8px;border:2px solid rgba(255,255,255,.45);border-top-color:#fff;border-radius:50%;vertical-align:-2px;animation:spin .7s linear infinite}
        @keyframes stage-in{from{opacity:0;transform:translateX(8px)}to{opacity:1;transform:none}}@keyframes spin{to{transform:rotate(360deg)}}
        @media(max-width:900px){.page{display:block;padding:22px}.shell{min-height:0;grid-template-columns:1fr}.side{padding:27px 32px}.logo{width:200px;height:65px}.side-copy{margin-top:23px}.side h1{max-width:620px;font-size:1.72rem}.side h1 span{display:inline}.side h1 span+span:before{content:" "}.side-lead{max-width:650px;margin-top:10px}.highlights,.privacy{display:none}.main{min-height:670px;padding:34px 36px}.heading{margin:18px 0 24px}}
        @media(max-width:640px){.page{padding:0}.shell{border:0;border-radius:0;box-shadow:none}.side{padding:22px 20px}.logo{width:176px;height:52px}.side-copy{margin-top:16px}.eyebrow{margin-bottom:8px}.side h1{font-size:1.42rem}.side-lead{display:none}.main{min-height:calc(100vh - 173px);padding:25px 20px}.topbar{align-items:flex-start}.application-tag{font-size:.68rem}.login-link{font-size:.73rem}.heading{margin:19px 0 22px}.heading h2{font-size:1.55rem}.heading p{font-size:.82rem}.progress{margin-bottom:25px}.progress-step{display:block;padding:0 3px 11px}.progress-step:not(:last-child){margin-right:7px}.progress-no{width:25px;height:25px;margin-bottom:5px}.progress-label{font-size:.64rem}.stage-head{margin-bottom:20px}.stage h3{font-size:1.12rem}.required-note{display:none}.grid,.plans{grid-template-columns:1fr}.grid{gap:17px}.input{height:51px}.plans{gap:18px}.plan{min-height:0;padding:20px}.price{margin:16px 0 14px;font-size:1.7rem}.plan ul{margin-top:0}.review{grid-template-columns:1fr}.consent{margin-top:17px}.actions{padding-top:24px}.step-count{display:none}.actions-right{width:100%}.btn{flex:1;padding:0 14px}.btn-primary{min-width:0}}
        @media(prefers-reduced-motion:reduce){*,*:before,*:after{scroll-behavior:auto!important;animation:none!important;transition:none!important}}
    </style>
</head>
<body>
<main class="page">
    <div class="shell">
        <aside class="side">
            <img class="logo" src="images/DRS_Logo_White.png" alt="Digital Registration System">
            <div class="side-copy">
                <p class="eyebrow">Sistem RMT digital</p>
                <h1><span>Permohonan mudah.</span><span>Pengurusan lebih yakin.</span></h1>
                <p class="side-lead">Mulakan dengan tiga langkah ringkas. Kami bantu sehingga sistem sekolah anda sedia digunakan.</p>
            </div>
            <div class="highlights">
                <p class="highlights-title">Setiap pelan merangkumi</p>
                <ul><li>Pembaca RFID untuk sekolah</li><li>Pemasangan dan bantuan persediaan</li><li>Sokongan teknikal berterusan</li></ul>
            </div>
            <div class="privacy"><span class="privacy-mark">◆</span><span><strong>Permohonan dilindungi</strong>Maklumat hanya digunakan untuk semakan dan pengurusan akaun sekolah.</span></div>
        </aside>

        <section class="main">
            <div class="topbar"><span class="application-tag">Permohonan baharu</span><a class="login-link" href="index.php">Sudah berdaftar? Log masuk →</a></div>
            <header class="heading"><h2>Daftar sekolah anda</h2><p>Lengkapkan tiga langkah ringkas untuk menghantar permohonan.</p></header>

            <nav class="progress" aria-label="Kemajuan permohonan">
                <button type="button" class="progress-step active" data-target="1" aria-current="step"><span class="progress-no">1</span><span class="progress-label">Maklumat sekolah</span></button>
                <button type="button" class="progress-step" data-target="2"><span class="progress-no">2</span><span class="progress-label">Pilihan pelan</span></button>
                <button type="button" class="progress-step" data-target="3"><span class="progress-no">3</span><span class="progress-label">Akaun pentadbir</span></button>
            </nav>

            <div id="formAlert" class="alert" role="alert" aria-live="polite"></div>
            <form id="publicRegisterForm">
                <input type="hidden" name="csrf_token" value="<?= escape_html($csrf) ?>">
                <input type="hidden" name="action" value="register_public">

                <section class="stage" data-stage="1">
                    <div class="stage-head"><div><p class="stage-kicker">Langkah 1 daripada 3</p><h3>Maklumat rasmi sekolah</h3><p class="stage-description">Gunakan butiran yang boleh disahkan oleh pihak sekolah.</p></div><span class="required-note"><span class="required">*</span> Medan wajib</span></div>
                    <div class="grid">
                        <div class="field"><label for="schoolCode">Kod sekolah <span class="required">*</span></label><input class="input" id="schoolCode" name="kod_sekolah" type="text" required maxlength="20" autocomplete="organization" placeholder="Contoh: KBA1234"></div>
                        <div class="field"><label for="schoolName">Nama sekolah <span class="required">*</span></label><input class="input" id="schoolName" name="nama_sekolah" type="text" required maxlength="150" autocomplete="organization" placeholder="Contoh: SK Kuala Pegang"></div>
                        <div class="field"><label for="schoolEmail">E-mel rasmi sekolah <span class="required">*</span></label><input class="input" id="schoolEmail" name="email_sekolah" type="email" required maxlength="100" autocomplete="email" placeholder="admin@sekolah.edu.my"></div>
                        <div class="field"><label for="schoolPhone">Nombor telefon sekolah</label><input class="input" id="schoolPhone" name="no_tel" type="tel" maxlength="20" autocomplete="tel" placeholder="04-123 4567"></div>
                    </div>
                </section>

                <section class="stage" data-stage="2" hidden>
                    <div class="stage-head"><div><p class="stage-kicker">Langkah 2 daripada 3</p><h3>Pilih pelan langganan</h3><p class="stage-description">Pilih kapasiti yang paling sesuai dengan keperluan sekolah.</p></div></div>
                    <div class="plans" role="radiogroup" aria-label="Pilihan pelan langganan">
                        <label class="plan selected"><input type="radio" name="pelan" value="Basic" checked required><div class="plan-top"><div><span class="plan-name">Basic</span><span class="plan-caption">Untuk sekolah berskala kecil</span></div><span class="radio" aria-hidden="true"></span></div><div class="price"><sup>RM</sup>150 <span>/ tahun pertama</span></div><ul><li>Sehingga 80 murid RMT</li><li>1 pembaca RFID & pemasangan</li><li>Sokongan teknikal</li></ul></label>
                        <label class="plan"><span class="popular">DISYORKAN</span><input type="radio" name="pelan" value="Pro" required><div class="plan-top"><div><span class="plan-name">Pro</span><span class="plan-caption">Untuk kapasiti tanpa had</span></div><span class="radio" aria-hidden="true"></span></div><div class="price"><sup>RM</sup>300 <span>/ tahun pertama</span></div><ul><li>Bilangan murid tanpa had</li><li>1 pembaca RFID & pemasangan</li><li>Sokongan teknikal</li></ul></label>
                    </div>
                    <p class="plan-note"><span>ⓘ</span><span><strong>Nota:</strong> Kad RFID dijual berasingan dan boleh dibeli sendiri atau melalui pihak kami.</span></p>
                </section>

                <section class="stage" data-stage="3" hidden>
                    <div class="stage-head"><div><p class="stage-kicker">Langkah 3 daripada 3</p><h3>Cipta akaun pentadbir</h3><p class="stage-description">Akaun utama untuk mengurus sistem selepas pengaktifan.</p></div><span class="required-note"><span class="required">*</span> Medan wajib</span></div>
                    <div class="review"><div class="review-item"><span>Sekolah</span><strong id="reviewSchool">Belum dinyatakan</strong></div><div class="review-item"><span>Pelan dipilih</span><strong id="reviewPlan">Basic · RM150/tahun pertama</strong></div></div>
                    <div class="grid">
                        <div class="field"><label for="adminName">Nama penuh pentadbir <span class="required">*</span></label><input class="input" id="adminName" name="admin_nama" type="text" required maxlength="100" autocomplete="name" placeholder="Nama seperti kad pengenalan"></div>
                        <div class="field"><label for="adminEmail">E-mel pentadbir <span class="required">*</span></label><input class="input" id="adminEmail" name="admin_email" type="email" required maxlength="100" autocomplete="email" placeholder="nama@sekolah.edu.my"></div>
                        <div class="field"><label for="adminUsername">Nama pengguna <span class="required">*</span></label><input class="input" id="adminUsername" name="admin_username" type="text" required minlength="3" maxlength="50" autocomplete="username" pattern="[A-Za-z0-9._-]+" placeholder="Contoh: admin_skkp"><p class="hint">Huruf, nombor, titik, sengkang atau garis bawah sahaja.</p></div>
                        <div class="field"><label for="adminPassword">Kata laluan <span class="required">*</span></label><div class="password-wrap"><input class="input" id="adminPassword" name="admin_password" type="password" required minlength="10" maxlength="128" autocomplete="new-password" placeholder="Minimum 10 aksara"><button class="toggle-password" type="button" id="togglePassword" aria-controls="adminPassword">Lihat</button></div><p class="hint">Gunakan gabungan huruf, nombor dan simbol.</p></div>
                    </div>
                    <label class="consent"><input type="checkbox" id="consent" required><span>Saya mengesahkan maklumat yang diberikan adalah tepat dan saya diberi kuasa untuk membuat permohonan bagi pihak sekolah.</span></label>
                </section>

                <footer class="actions"><span class="step-count" id="stepCount">Langkah 1 daripada 3</span><div class="actions-right"><button class="btn btn-secondary" id="backButton" type="button" hidden>Kembali</button><button class="btn btn-primary" id="nextButton" type="button">Teruskan</button><button class="btn btn-primary" id="submitButton" type="submit" hidden>Hantar permohonan</button></div></footer>
            </form>
        </section>
    </div>
</main>
<script>
(() => {
    const form = document.getElementById('publicRegisterForm');
    const stages = [...document.querySelectorAll('[data-stage]')];
    const progress = [...document.querySelectorAll('.progress-step')];
    const plans = [...document.querySelectorAll('.plan')];
    const backButton = document.getElementById('backButton');
    const nextButton = document.getElementById('nextButton');
    const submitButton = document.getElementById('submitButton');
    const stepCount = document.getElementById('stepCount');
    const alertBox = document.getElementById('formAlert');
    const schoolName = document.getElementById('schoolName');
    const password = document.getElementById('adminPassword');
    const togglePassword = document.getElementById('togglePassword');
    let currentStep = 1;
    let furthestStep = 1;

    const showMessage = (message, type) => {
        alertBox.textContent = message;
        alertBox.className = `alert show ${type}`;
    };
    const clearMessage = () => { alertBox.className = 'alert'; alertBox.textContent = ''; };
    const updateReview = () => {
        document.getElementById('reviewSchool').textContent = schoolName.value.trim() || 'Belum dinyatakan';
        const selected = form.querySelector('input[name="pelan"]:checked').value;
        document.getElementById('reviewPlan').textContent = selected === 'Pro' ? 'Pro · RM300/tahun pertama' : 'Basic · RM150/tahun pertama';
    };
    const renderStep = () => {
        stages.forEach(stage => { stage.hidden = Number(stage.dataset.stage) !== currentStep; });
        progress.forEach((item, index) => {
            const step = index + 1;
            item.classList.toggle('active', step === currentStep);
            item.classList.toggle('done', step !== currentStep && step <= furthestStep);
            item.setAttribute('aria-current', step === currentStep ? 'step' : 'false');
        });
        backButton.hidden = currentStep === 1;
        nextButton.hidden = currentStep === 3;
        submitButton.hidden = currentStep !== 3;
        stepCount.textContent = `Langkah ${currentStep} daripada 3`;
        if (currentStep === 3) updateReview();
        clearMessage();
    };
    const validateCurrentStep = () => {
        const currentStage = stages[currentStep - 1];
        const fields = [...currentStage.querySelectorAll('input[required]')];
        const invalid = fields.find(field => !field.checkValidity());
        if (invalid) { invalid.reportValidity(); invalid.focus(); return false; }
        return true;
    };

    nextButton.addEventListener('click', () => {
        if (!validateCurrentStep()) return;
        currentStep = Math.min(3, currentStep + 1);
        furthestStep = Math.max(furthestStep, currentStep);
        renderStep();
    });
    backButton.addEventListener('click', () => { currentStep = Math.max(1, currentStep - 1); renderStep(); });
    progress.forEach(item => item.addEventListener('click', () => {
        const target = Number(item.dataset.target);
        if (target <= furthestStep && target !== currentStep) { currentStep = target; renderStep(); }
    }));
    plans.forEach(card => card.querySelector('input').addEventListener('change', () => {
        plans.forEach(item => item.classList.toggle('selected', item.querySelector('input').checked));
        updateReview();
    }));
    togglePassword.addEventListener('click', () => {
        const visible = password.type === 'text';
        password.type = visible ? 'password' : 'text';
        togglePassword.textContent = visible ? 'Lihat' : 'Sorok';
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (!validateCurrentStep() || !form.reportValidity()) return;
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner"></span>Menghantar…';
        clearMessage();
        try {
            const response = await fetch('api_public.php', {method: 'POST', headers: {Accept: 'application/json'}, body: new FormData(form)});
            const data = await response.json();
            if (!response.ok || data.status !== 'success') throw new Error(data.msg || 'Permohonan tidak dapat dihantar.');
            showMessage(data.msg, 'success');
            form.reset();
            plans.forEach(item => item.classList.toggle('selected', item.querySelector('input').checked));
            submitButton.textContent = 'Permohonan dihantar';
            setTimeout(() => { window.location.href = 'index.php'; }, 2800);
        } catch (error) {
            showMessage(error.message || 'Gagal berhubung dengan pelayan. Sila cuba lagi.', 'error');
            submitButton.disabled = false;
            submitButton.textContent = 'Hantar permohonan';
        }
    });

    renderStep();
})();
</script>
</body>
</html>
