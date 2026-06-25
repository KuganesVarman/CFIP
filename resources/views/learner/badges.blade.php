@extends('layouts.learner')

@section('title', 'Badges & Certificates')
@section('page-title', 'Badges & Certificates')

@push('topbar-actions')
@php $lessonsActive = $includeLessons ?? false; @endphp
<style>
.lesson-toggle-btn{display:inline-flex;align-items:center;gap:5px;padding:5px 11px;font-size:12px;font-weight:600;font-family:inherit;border-radius:6px;border:1.5px solid var(--border,#e5e7eb);background:var(--bg-card,#fff);color:var(--text-secondary,#6b7280);cursor:pointer;transition:all .15s ease;white-space:nowrap}
.lesson-toggle-btn:hover{border-color:#4f6ef7;color:#4f6ef7}
.lesson-toggle-btn.active{background:#4f6ef7;border-color:#4f6ef7;color:#fff}
</style>
<div style="display:flex;align-items:center;margin-left:auto;margin-right:12px">
    <button type="button" class="lesson-toggle-btn{{ $lessonsActive ? ' active' : '' }}" onclick="toggleLessons()" title="{{ $lessonsActive ? 'Click to exclude lesson results' : 'Click to include lesson results' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;flex-shrink:0"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
        Lessons {{ $lessonsActive ? 'ON' : 'OFF' }}
    </button>
</div>
<script>
function toggleLessons(){var u=new URL(window.location.href);u.searchParams.has('include_lessons')?u.searchParams.delete('include_lessons'):u.searchParams.set('include_lessons','1');window.location.href=u.toString();}
</script>
@endpush

@section('content')

@php
    $earnedCount = collect($domains)->filter(fn($d) => $d['earned'])->count();
    $totalBadges = count($domains) + 1; // 5 domain + 1 cert

    $badgeImages = [
        'foundation'     => 'Foundation_Badge.png',
        'legal_ethics'   => 'Legal_&_Ethics_Badge.png',
        'crime_inv'      => 'Crime_Investigation_Badge.png',
        'soft_skills'    => 'Soft_Skills_Competencies_Badge.png',
        'inv_techniques' => 'Investigation_Techniques_Badge.png',
    ];
@endphp

<style>
.ld-badge-img-wrap {
    width: 130px;
    height: 130px;
    margin: 0 auto 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.ld-badge-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    transition: filter 0.2s;
}
.ld-badge-img.locked {
    filter: grayscale(100%) opacity(0.3);
}
</style>

{{-- ──── PAGE HEADER ─────────────────────────────────────── --}}
<div class="ld-page-header">
    <div class="ld-page-header-left">
        <div class="ld-page-header-super">My Achievements</div>
        <div class="ld-page-header-sub">Track your badges and certificates earned in the CFIP programme</div>
    </div>
    <div class="ld-badge-count-chip">
        <span class="ld-badge-count-num">{{ $earnedCount }}</span>
        <span class="ld-badge-count-label">/ {{ $totalBadges }} Badges Earned</span>
    </div>
</div>

{{-- ──── DOMAIN BADGES GRID ─────────────────────────────── --}}
<div class="ld-section-title">Domain Badges</div>
<div class="ld-badge-grid">

    @foreach ($domains as $domain)
    @php
        $badgeImg      = $badgeImages[$domain['code']] ?? 'Foundation_Badge.png';
        $earnedYear    = $domain['earned'] && $domain['earned_date'] ? \Carbon\Carbon::parse($domain['earned_date'])->year : null;
        $earnedMonth   = $domain['earned'] && $domain['earned_date'] ? \Carbon\Carbon::parse($domain['earned_date'])->month : null;
        $earnedDateFmt = $domain['earned'] && $domain['earned_date'] ? \Carbon\Carbon::parse($domain['earned_date'])->format('d M Y') : '';
    @endphp

    <div class="ld-badge-card{{ $domain['earned'] ? ' earned' : '' }}"
         style="{{ $domain['earned'] ? 'border-color:'.$domain['color'].';box-shadow:0 0 0 4px '.$domain['color'].'1a' : '' }}">

        {{-- Badge image --}}
        <div class="ld-badge-img-wrap">
            <img src="{{ asset('image/' . $badgeImg) }}"
                 alt="{{ $domain['name'] }} Badge"
                 class="ld-badge-img{{ $domain['earned'] ? '' : ' locked' }}">
        </div>

        {{-- Earned / Locked tag --}}
        @if ($domain['earned'])
            <span class="ld-badge-earned-tag">✓ Earned</span>
        @else
            <span class="ld-badge-locked-tag">🔒 Locked</span>
        @endif

        {{-- Badge name --}}
        <div class="ld-badge-name" style="color:{{ $domain['earned'] ? '#111827' : '#9ca3af' }}">
            {{ $domain['name'] }}
        </div>

        {{-- Programme label --}}
        <div class="ld-badge-programme">CFIP Entry Level</div>

        @if ($domain['earned'])
            {{-- Earned date --}}
            <div class="ld-badge-date">Earned {{ $earnedDateFmt }}</div>

            {{-- Share/Download buttons --}}
            <div class="ld-badge-actions">
                <button class="ld-badge-btn ld-badge-btn-wa"
                        onclick="shareBadgeWA('{{ addslashes($domain['name']) }}')"
                        title="Share on WhatsApp">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    WhatsApp
                </button>
                <button class="ld-badge-btn ld-badge-btn-li"
                        onclick="shareBadgeLI('{{ addslashes($domain['name']) }}','{{ $domain['slug'] }}',{{ $earnedYear ?? 'null' }},{{ $earnedMonth ?? 'null' }})"
                        title="Add to LinkedIn">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    LinkedIn
                </button>
                <button class="ld-badge-btn ld-badge-btn-dl"
                        onclick="downloadBadge('{{ addslashes($domain['name']) }}','{{ $earnedDateFmt }}','{{ asset('image/'.$badgeImg) }}')"
                        title="Download badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Download
                </button>
            </div>

        @else
            {{-- Progress towards badge --}}
            <div class="ld-badge-prog">{{ $domain['modules_passed'] }} / {{ $domain['modules_total'] }} modules completed</div>
            <div class="ld-badge-prog-bar-bg">
                <div class="ld-badge-prog-bar-fill"
                     style="width:{{ $domain['progress'] }}%;background:{{ $domain['color'] }}"></div>
            </div>
            <div class="ld-badge-req">Complete all {{ $domain['name'] }} modules to unlock</div>

            <div class="ld-badge-actions">
                <a href="{{ route('learner.modules', ['domain' => $domain['slug']]) }}"
                   class="ld-badge-btn ld-badge-btn-continue" style="text-decoration:none">
                    Continue Learning →
                </a>
            </div>
        @endif

    </div>
    @endforeach

</div>

{{-- ──── ENTRY LEVEL CERTIFICATE ────────────────────────── --}}
<div class="ld-section-title" style="margin-top:8px">Certificate</div>

@php
    $certId = 'CFIP-EL-' . date('Y') . '-' . str_pad(auth()->id(), 4, '0', STR_PAD_LEFT);
    $certDateFmt = $certEarnedDate ? \Carbon\Carbon::parse($certEarnedDate)->format('d M Y') : '';
    $certYear    = $certEarnedDate ? \Carbon\Carbon::parse($certEarnedDate)->year : null;
    $certMonth   = $certEarnedDate ? \Carbon\Carbon::parse($certEarnedDate)->month : null;
@endphp

<div class="ld-cert-card{{ $entryLevelComplete ? ' earned' : '' }}">
    <div class="ld-cert-inner">

        {{-- LEFT: Certificate preview --}}
        <div class="ld-cert-preview{{ $entryLevelComplete ? '' : ' locked' }}">
            <div class="ld-cert-header-text">Certified Financial Investigator Programme</div>
            <div class="ld-cert-certifies">This certifies that</div>
            <div class="ld-cert-name">{{ $learnerName }}</div>
            <div class="ld-cert-completed">has successfully completed</div>
            <div class="ld-cert-programme">CFIP Entry Level Programme</div>
            <div class="ld-cert-footer">
                <span class="ld-cert-footer-text">Issued: {{ $entryLevelComplete ? $certDateFmt : '—' }}</span>
                <span class="ld-cert-footer-text">ID: {{ $certId }}</span>
            </div>
        </div>

        {{-- RIGHT: Actions --}}
        <div class="ld-cert-actions">
            @if ($entryLevelComplete)
                <div class="ld-cert-heading">Certificate Earned</div>
                <div class="ld-cert-sub">CFIP Entry Level Programme</div>
                <div class="ld-cert-earned-date">Earned {{ $certDateFmt }}</div>
                <div class="ld-cert-id">ID: {{ $certId }}</div>

                <div class="ld-cert-btn-list">
                    <button class="ld-cert-btn ld-cert-btn-pdf"
                            onclick="downloadCertificatePDF('{{ addslashes($learnerName) }}','{{ $certDateFmt }}','{{ $certId }}')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="12" y1="18" x2="12" y2="12"/>
                            <polyline points="9 15 12 18 15 15"/>
                        </svg>
                        Download PDF Certificate
                    </button>
                    <button class="ld-cert-btn ld-cert-btn-wa"
                            onclick="shareCertWA('{{ $certId }}')">
                        <svg viewBox="0 0 24 24" fill="currentColor" style="width:15px;height:15px"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        Share on WhatsApp
                    </button>
                    <button class="ld-cert-btn ld-cert-btn-li"
                            onclick="shareCertLI('{{ $certId }}',{{ $certYear ?? 'null' }},{{ $certMonth ?? 'null' }})">
                        <svg viewBox="0 0 24 24" fill="currentColor" style="width:15px;height:15px"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        Add to LinkedIn
                    </button>
                    <button class="ld-cert-btn ld-cert-btn-credly"
                            onclick="openCredly()">
                        <svg viewBox="0 0 24 24" fill="currentColor" style="width:15px;height:15px;color:#f97316"><circle cx="12" cy="12" r="11" fill="none" stroke="#f97316" stroke-width="2"/><text x="12" y="17" text-anchor="middle" font-size="13" font-weight="700" fill="#f97316" font-family="sans-serif">C</text></svg>
                        Add to Credly
                    </button>
                </div>
                <div class="ld-credly-note">ⓘ Ask your coordinator to issue your Credly badge</div>

            @else
                {{-- Locked state --}}
                <div class="ld-cert-locked-heading">Certificate Locked</div>
                @php $domainsEarned = collect($domains)->filter(fn($d) => $d['earned'])->count(); @endphp
                <div class="ld-cert-locked-progress">{{ $domainsEarned }} / 5 domains completed</div>

                {{-- Stacked progress bar --}}
                <div style="height:8px;border-radius:4px;background:#f3f4f6;overflow:hidden;margin-bottom:14px">
                    <div style="height:100%;width:{{ ($domainsEarned / 5) * 100 }}%;background:#1a4fa8;border-radius:4px"></div>
                </div>

                <ul class="ld-cert-domain-list">
                    @foreach ($domains as $d)
                    <li class="{{ $d['earned'] ? 'done' : 'todo' }}">
                        @if ($d['earned'])
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px;flex-shrink:0"><polyline points="20 6 9 17 4 12"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px;flex-shrink:0"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        @endif
                        {{ $d['name'] }}
                    </li>
                    @endforeach
                </ul>
                <div class="ld-cert-locked-msg">Complete all 5 domain badges to unlock your Entry Level Certificate</div>
            @endif
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
function shareBadgeWA(badgeName) {
    const msg = encodeURIComponent(
        `I just earned the ${badgeName} badge in the CFIP programme! 🎓 #CFIP #FinancialInvestigator`
    );
    window.open(`https://wa.me/?text=${msg}`, '_blank');
}

function shareBadgeLI(badgeName, slug, year, month) {
    const params = new URLSearchParams({
        startTask: 'CERTIFICATION_NAME',
        name: `${badgeName} — CFIP Entry Level`,
        organizationId: '',
        issueYear: year || '',
        issueMonth: month || '',
        certUrl: window.location.href,
        certId: `CFIP-${slug.toUpperCase()}-${year || new Date().getFullYear()}`
    });
    window.open(`https://www.linkedin.com/profile/add?${params.toString()}`, '_blank');
}

function downloadBadge(badgeName, earnedDate, imageUrl) {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = function () {
        const canvas = document.createElement('canvas');
        canvas.width  = img.naturalWidth  || 400;
        canvas.height = img.naturalHeight || 400;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        const link    = document.createElement('a');
        link.download = `CFIP_Badge_${badgeName.replace(/ /g,'_')}.png`;
        link.href     = canvas.toDataURL('image/png');
        link.click();
    };
    img.onerror = function () {
        // Fallback: direct link download if canvas draw fails (CORS)
        const link    = document.createElement('a');
        link.href     = imageUrl;
        link.download = `CFIP_Badge_${badgeName.replace(/ /g,'_')}.png`;
        link.click();
    };
    img.src = imageUrl;
}

function shareCertWA(certId) {
    const msg = encodeURIComponent(
        `I have completed the CFIP Entry Level Programme and earned my certificate! 🎓 Certificate ID: ${certId} #CFIP #CertifiedFinancialInvestigator`
    );
    window.open(`https://wa.me/?text=${msg}`, '_blank');
}

function shareCertLI(certId, year, month) {
    const params = new URLSearchParams({
        startTask: 'CERTIFICATION_NAME',
        name: 'CFIP Entry Level Programme',
        organizationId: '',
        issueYear: year || '',
        issueMonth: month || '',
        certUrl: window.location.href,
        certId: certId
    });
    window.open(`https://www.linkedin.com/profile/add?${params.toString()}`, '_blank');
}

function openCredly() {
    window.open('https://credly.com', '_blank');
}

function downloadCertificatePDF(learnerName, earnedDate, certId) {
    if (!window.jspdf || !window.jspdf.jsPDF) {
        alert('PDF library not loaded. Please refresh and try again.');
        return;
    }
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

    // Navy header band
    doc.setFillColor(30, 45, 74);
    doc.rect(0, 0, 297, 40, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(14);
    doc.setFont('helvetica', 'bold');
    doc.text('CERTIFIED FINANCIAL INVESTIGATOR PROGRAMME', 148, 18, { align: 'center' });
    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    doc.text('Ministry of Finance Malaysia', 148, 30, { align: 'center' });

    // Body
    doc.setTextColor(30, 45, 74);
    doc.setFontSize(12);
    doc.setFont('helvetica', 'normal');
    doc.text('This is to certify that', 148, 60, { align: 'center' });
    doc.setFontSize(22);
    doc.setFont('helvetica', 'bold');
    doc.text(learnerName, 148, 76, { align: 'center' });
    doc.setFontSize(12);
    doc.setFont('helvetica', 'normal');
    doc.text('has successfully completed the', 148, 90, { align: 'center' });
    doc.setFontSize(16);
    doc.setFont('helvetica', 'bold');
    doc.text('CFIP Entry Level Programme', 148, 102, { align: 'center' });

    // Footer
    doc.setFontSize(9);
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(100, 100, 100);
    doc.text(`Issued: ${earnedDate}`, 40, 185);
    doc.text(`Certificate ID: ${certId}`, 148, 185, { align: 'center' });
    doc.text('CFIP — CONFIDENTIAL', 257, 185, { align: 'right' });

    doc.save(`CFIP_Certificate_${learnerName.replace(/ /g,'_')}.pdf`);
}
</script>
@endpush
