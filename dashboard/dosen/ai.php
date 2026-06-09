<?php
require_once __DIR__ . '/../../auth/check.php';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/navigasi.php';

$user_id = $user['id'] ?? 0;
$dosen_id = 0;
$nama_dosen = '';
if ($user_id) {
    $stmt = $conn->prepare("SELECT id, nama FROM dosen WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($dosen_id, $nama_dosen);
    $stmt->fetch();
    $stmt->close();
}
if (!$dosen_id) {
    echo "<div class='alert alert-danger'>Data dosen tidak ditemukan.</div>";
    require_once __DIR__ . '/footer.php';
    exit;
}

// Ambil daftar mata kuliah yang diampu dosen
$sqlMatkul = "SELECT DISTINCT mk.id, mk.nama_mk 
              FROM jadwal j 
              JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id 
              WHERE j.dosen_id = ? 
              ORDER BY mk.nama_mk";
$stmtMatkul = $conn->prepare($sqlMatkul);
$stmtMatkul->bind_param('i', $dosen_id);
$stmtMatkul->execute();
$matkulResult = $stmtMatkul->get_result();
$daftarMatkul = [];
while ($row = $matkulResult->fetch_assoc()) {
    $daftarMatkul[] = $row;
}
?>

<style>
/* Tambahan CSS untuk modal yang benar-benar di tengah */
.modal-overlay-custom {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    align-items: center;
    justify-content: center;
}
.modal-overlay-custom.show {
    display: flex;
}
.modal-container {
    background: white;
    border-radius: 20px;
    width: 90%;
    max-width: 480px;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    animation: modalPop 0.2s ease-out;
}
@keyframes modalPop {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
.modal-header-custom {
    padding: 18px 20px;
    border-bottom: 1px solid #e5e7eb;
    background: #f8fafc;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header-custom h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
}
.modal-close {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: #94a3b8;
    line-height: 1;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
}
.modal-close:hover {
    background: #e2e8f0;
    color: #475569;
}
.modal-body-custom {
    padding: 24px;
    max-height: 60vh;
    overflow-y: auto;
}
.modal-footer-custom {
    padding: 16px 20px;
    border-top: 1px solid #e5e7eb;
    background: #f8fafc;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}
/* Loading dots */
.loading-dots {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.loading-dots span {
    width: 8px;
    height: 8px;
    background-color: #94a3b8;
    border-radius: 50%;
    animation: bounce 1.4s infinite ease-in-out both;
}
.loading-dots span:nth-child(1) { animation-delay: -0.32s; }
.loading-dots span:nth-child(2) { animation-delay: -0.16s; }
@keyframes bounce {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}
/* Tabel konfirmasi */
.confirm-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 8px;
}
.confirm-table th {
    text-align: left;
    padding: 10px 8px 8px 0;
    font-weight: 600;
    color: #475569;
    border-bottom: 2px solid #e2e8f0;
}
.confirm-table td {
    padding: 10px 8px;
    border-bottom: 1px solid #f1f5f9;
}
.confirm-table tr:last-child td {
    border-bottom: none;
}
.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}
.status-sakit {
    background: #fee2e2;
    color: #991b1b;
}
.status-izin {
    background: #fef3c7;
    color: #92400e;
}
.confirm-note {
    margin-top: 16px;
    padding-top: 12px;
    text-align: center;
    font-size: 13px;
    color: #64748b;
    border-top: 1px solid #e2e8f0;
}
.confirm-pertemuan {
    text-align: center;
    margin-bottom: 20px;
    padding: 8px;
    background: #e0f2fe;
    border-radius: 12px;
    color: #0369a1;
    font-weight: 600;
    font-size: 14px;
}
/* Chat bubble */
.chat-bubble-user {
    background: #4f46e5;
    color: white;
    padding: 10px 16px;
    border-radius: 20px;
    border-bottom-right-radius: 4px;
    max-width: 70%;
}
.chat-bubble-ai {
    background: #e5e7eb;
    color: #1f2937;
    padding: 10px 16px;
    border-radius: 20px;
    border-bottom-left-radius: 4px;
    max-width: 70%;
}
.btn-option {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-option-success {
    background: #10b981;
    color: white;
}
.btn-option-success:hover {
    background: #059669;
}
.btn-option-secondary {
    background: #f3f4f6;
    color: #4b5563;
}
.btn-option-secondary:hover {
    background: #e5e7eb;
}
.btn-outline {
    background: transparent;
    border: 1px solid #4f46e5;
    color: #4f46e5;
}
.btn-outline:hover {
    background: #4f46e5;
    color: white;
}
</style>

<div class="page-header">
    <div>
     
        <div class="page-subtitle">Kecerdasan buatan untuk mengelola kehadiran</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Percakapan dengan AI</div>
    </div>
    <div class="card-body">
        <div id="chatBox" style="height: 420px; overflow-y: auto; background: #f9fafb; border-radius: 16px; padding: 16px; margin-bottom: 16px; border: 1px solid #e5e7eb;">
        </div>
        <div style="display: flex; gap: 10px;">
            <input type="text" id="chatInput" class="form-control" placeholder="Ketik perintah..." style="flex: 1;" disabled>
            <button class="btn btn-primary" id="btnSend" disabled>Kirim</button>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Custom -->
<div id="confirmModal" class="modal-overlay-custom">
    <div class="modal-container">
        <div class="modal-header-custom">
            <h3>Konfirmasi Kehadiran</h3>
            <button class="modal-close" onclick="closeConfirmModal()">&times;</button>
        </div>
        <div class="modal-body-custom" id="confirmBody"></div>
        <div class="modal-footer-custom">
            <button class="btn-option btn-option-secondary" onclick="closeConfirmModal()">Batal</button>
            <button class="btn-option btn-option-success" id="confirmBtn">Ya, Proses Absensi</button>
        </div>
    </div>
</div>

<script>
let currentStep = 'start';
let selectedMkId = null;
let selectedMkName = null;
let selectedKelasId = null;
let selectedKelasName = null;
let pendingData = null;

const chatBox = document.getElementById('chatBox');
const chatInput = document.getElementById('chatInput');
const btnSend = document.getElementById('btnSend');
const daftarMatkul = <?= json_encode($daftarMatkul) ?>;
const namaDosen = <?= json_encode($nama_dosen) ?>;

function addMessage(text, isUser = false, options = null) {
    const msgDiv = document.createElement('div');
    msgDiv.style.marginBottom = '14px';
    msgDiv.style.display = 'flex';
    msgDiv.style.justifyContent = isUser ? 'flex-end' : 'flex-start';
    
    const bubble = document.createElement('div');
    bubble.className = isUser ? 'chat-bubble-user' : 'chat-bubble-ai';
    
    if (options && options.buttons) {
        bubble.innerHTML = text + '<br><div style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap;">' + options.buttons + '</div>';
    } else {
        bubble.innerText = text;
    }
    msgDiv.appendChild(bubble);
    chatBox.appendChild(msgDiv);
    chatBox.scrollTop = chatBox.scrollHeight;
    return bubble;
}

function addLoadingMessage() {
    const msgDiv = document.createElement('div');
    msgDiv.style.marginBottom = '14px';
    msgDiv.style.display = 'flex';
    msgDiv.style.justifyContent = 'flex-start';
    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble-ai';
    bubble.innerHTML = '<div class="loading-dots"><span></span><span></span><span></span></div>';
    msgDiv.appendChild(bubble);
    chatBox.appendChild(msgDiv);
    chatBox.scrollTop = chatBox.scrollHeight;
    return msgDiv;
}

function removeLoadingMessage(loadingElement) {
    if (loadingElement && loadingElement.parentNode) {
        loadingElement.remove();
    }
}

function clearChatInput() {
    chatInput.value = '';
    chatInput.disabled = true;
    btnSend.disabled = true;
}

function enableChatInput() {
    chatInput.disabled = false;
    btnSend.disabled = false;
    chatInput.focus();
}

function showMatkulList() {
    let buttons = '';
    daftarMatkul.forEach(mk => {
        buttons += `<button class="btn-option btn-outline" style="margin:4px;" onclick="pilihMatkul(${mk.id}, '${mk.nama_mk.replace(/'/g, "\\'")}')">${mk.nama_mk}</button>`;
    });
    addMessage(`Selamat datang, ${namaDosen}. Berikut mata kuliah yang Anda ampu. Silakan pilih:`, false, { buttons: buttons });
    currentStep = 'pilih_matkul';
    clearChatInput();
}

async function getNextPertemuan(mkId, kelasId) {
    try {
        const response = await fetch('api/ai.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get_next_pertemuan', mk_id: mkId, kelas_id: kelasId })
        });
        const data = await response.json();
        return data.success ? data.pertemuan_ke : 1;
    } catch (e) {
        return 1;
    }
}

function pilihMatkul(mkId, mkName) {
    selectedMkId = mkId;
    selectedMkName = mkName;
    addMessage(`Memilih mata kuliah: ${mkName}`, true);
    
    const loadingMsg = addLoadingMessage();
    
    fetch('api/ai.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_kelas_by_matkul', mk_id: mkId })
    })
    .then(res => res.json())
    .then(data => {
        removeLoadingMessage(loadingMsg);
        if (data.success && data.kelas.length > 0) {
            let buttons = '';
            data.kelas.forEach(k => {
                buttons += `<button class="btn-option btn-outline" style="margin:4px;" onclick="pilihKelas(${k.id}, '${k.nama_kelas.replace(/'/g, "\\'")}')">${k.nama_kelas}</button>`;
            });
            addMessage(`Pilih kelas untuk ${mkName}:`, false, { buttons: buttons });
            currentStep = 'pilih_kelas';
        } else {
            addMessage(`Tidak ada kelas untuk mata kuliah ${mkName}.`, false);
            showMatkulList();
        }
    });
}

async function pilihKelas(kelasId, kelasName) {
    selectedKelasId = kelasId;
    selectedKelasName = kelasName;
    addMessage(`Memilih kelas: ${kelasName}`, true);
    
    const loadingMsg = addLoadingMessage();
    const pertemuanKe = await getNextPertemuan(selectedMkId, selectedKelasId);
    removeLoadingMessage(loadingMsg);
    
    window.currentPertemuanKe = pertemuanKe;
    
    addMessage(`Apakah Anda ingin menandai semua mahasiswa hadir untuk ${selectedMkName} kelas ${selectedKelasName} (Pertemuan ke-${pertemuanKe})?`, false, {
        buttons: `
            <button class="btn-option btn-option-success" onclick="hadirSemua()">Ya, Hadir Semua</button>
            <button class="btn-option btn-option-secondary" onclick="inputManual()">Tidak, input manual</button>
        `
    });
    currentStep = 'tanya_hadir_semua';
    clearChatInput();
}

function hadirSemua() {
    const pertemuanKe = window.currentPertemuanKe || 1;
    addMessage('Ya, hadir semua mahasiswa.', true);
    
    const loadingMsg = addLoadingMessage();
    
    fetch('api/ai.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'buat_sesi_dan_hadir_semua',
            mk_id: selectedMkId,
            kelas_id: selectedKelasId,
            pertemuan_ke: pertemuanKe
        })
    })
    .then(res => res.json())
    .then(data => {
        removeLoadingMessage(loadingMsg);
        if (data.success) {
            addMessage(data.message, false);
            addMessage(`Sesi untuk Pertemuan ke-${pertemuanKe} telah dibuat dan semua mahasiswa sudah ditandai HADIR.`, false);
            currentStep = 'start';
            enableChatInput();
        } else {
            addMessage(`Gagal: ${data.error}`, false);
            showMatkulList();
        }
    });
}

function inputManual() {
    const pertemuanKe = window.currentPertemuanKe || 1;
    addMessage('Tidak, saya akan input manual.', true);
    addMessage(`Silakan kirim pesan untuk Pertemuan ke-${pertemuanKe} dengan format:\n\n"yang tidak hadir [Nama] [sakit/izin]" \nContoh: "yang tidak hadir Ahmad sakit, Budi izin"`, false);
    currentStep = 'input_manual';
    window.tempPertemuanKe = pertemuanKe;
    enableChatInput();
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function prosesPesanManual(pesan) {
    if (!selectedMkId || !selectedKelasId) {
        addMessage('Silakan pilih mata kuliah dan kelas terlebih dahulu.', false);
        showMatkulList();
        return;
    }
    
    const loadingMsg = addLoadingMessage();
    
    fetch('api/ai.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'ekstrak_tidak_hadir',
            mk_id: selectedMkId,
            kelas_id: selectedKelasId,
            pesan: pesan
        })
    })
    .then(res => res.json())
    .then(data => {
        removeLoadingMessage(loadingMsg);
        if (data.success && data.tidak_hadir && data.tidak_hadir.length > 0) {
            pendingData = {
                mk_id: selectedMkId,
                kelas_id: selectedKelasId,
                tidak_hadir: data.tidak_hadir,
                pertemuan_ke: window.tempPertemuanKe || 1
            };
            
            let html = '';
            html += '<div class="confirm-pertemuan">Pertemuan ke-' + (window.tempPertemuanKe || 1) + '</div>';
            html += '<table class="confirm-table">';
            html += '<thead><tr><th>Nama Mahasiswa</th><th>Status</th></tr></thead><tbody>';
            
            data.tidak_hadir.forEach(item => {
                const statusClass = item.status === 'sakit' ? 'status-sakit' : 'status-izin';
                const statusText = item.status === 'sakit' ? 'Sakit' : 'Izin';
                html += `<tr>`;
                html += `<td><strong>${escapeHtml(item.nama)}</strong></td>`;
                html += `<td><span class="status-badge ${statusClass}">${statusText}</span></td>`;
                html += `</tr>`;
            });
            
            html += '</tbody></table>';
            html += '<div class="confirm-note">Mahasiswa lainnya akan ditandai HADIR.</div>';
            
            document.getElementById('confirmBody').innerHTML = html;
            showConfirmModal();
        } else {
            addMessage(`AI tidak bisa memahami: ${data.error || 'Tidak ada data ketidakhadiran'}`, false);
            addMessage('Coba lagi dengan format yang lebih jelas.', false);
        }
    });
}

function showConfirmModal() {
    const modal = document.getElementById('confirmModal');
    modal.classList.add('show');
}

function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    modal.classList.remove('show');
}

document.getElementById('confirmBtn').onclick = function() {
    if (!pendingData) return;
    closeConfirmModal();
    
    const loadingMsg = addLoadingMessage();
    
    fetch('api/ai.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'proses_absensi_manual',
            mk_id: pendingData.mk_id,
            kelas_id: pendingData.kelas_id,
            tidak_hadir: pendingData.tidak_hadir,
            pertemuan_ke: pendingData.pertemuan_ke
        })
    })
    .then(res => res.json())
    .then(data => {
        removeLoadingMessage(loadingMsg);
        if (data.success) {
            addMessage(data.message, false);
            addMessage(`Sesi untuk Pertemuan ke-${pendingData.pertemuan_ke} telah dibuat. ${data.hadir_count} mahasiswa hadir, ${data.tidak_hadir_count} mahasiswa tidak hadir.`, false);
            pendingData = null;
            currentStep = 'start';
            enableChatInput();
        } else {
            addMessage(`Gagal: ${data.error}`, false);
        }
    });
};

btnSend.onclick = function() {
    const msg = chatInput.value.trim();
    if (msg === '') return;
    if (currentStep === 'input_manual') {
        addMessage(msg, true);
        prosesPesanManual(msg);
        chatInput.value = '';
        chatInput.disabled = true;
        btnSend.disabled = true;
    } else {
        addMessage(msg, true);
        addMessage('Silakan ikuti opsi yang tersedia.', false);
    }
};
chatInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') btnSend.click();
});

// Inisialisasi
addMessage(`Selamat datang, ${namaDosen}. Saat ini tidak ada jadwal yang sedang berlangsung.`, false);
showMatkulList();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>