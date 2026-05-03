<?php ob_start(); ?>

<div class="page-header">
  <h1>Profile Settings</h1>
</div>

<div style="display:grid;grid-template-columns:220px 1fr 1fr;gap:24px;align-items:start">

  <!-- Profile Image -->
  <div class="card" style="text-align:center">
    <div class="card-body" style="padding:24px 16px">
      <div style="margin-bottom:16px">
        <img id="profileImgPreview"
          src="<?= htmlspecialchars(!empty($user['profile_image']) ? app_url($user['profile_image']) : asset_url('/assets/images/no-image.png')) ?>"
          alt="Profile"
          style="width:120px;height:120px;object-fit:cover;border-radius:50%;border:3px solid var(--color-primary);display:block;margin:0 auto 12px"
          onerror="this.src='<?= htmlspecialchars(asset_url('/assets/images/no-image.png')) ?>'"
        >
        <div style="font-weight:600;font-size:.95rem"><?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></div>
        <div style="font-size:.8rem;color:var(--color-gray-500)"><?= htmlspecialchars(implode(', ', array_column($user['roles'] ?? [], 'name'))) ?></div>
      </div>
      <label class="btn btn-secondary btn-sm" style="cursor:pointer;display:inline-block">
        <?= icon('upload', 14) ?> Change Photo
        <input type="file" id="profileImageInput" accept="image/*" style="display:none" onchange="handleProfileImageChange(event)">
      </label>
      <div id="profileImgActions" style="display:none;margin-top:8px;display:none">
        <button class="btn btn-primary btn-sm" onclick="uploadProfileImage()" id="saveImgBtn">Save Photo</button>
        <button class="btn btn-secondary btn-sm" onclick="cancelProfileImageChange()" style="margin-top:4px">Cancel</button>
      </div>
      <div style="font-size:.75rem;color:var(--color-gray-400);margin-top:8px">JPG, PNG, GIF, WEBP &middot; max 10MB</div>
    </div>
  </div>

  <!-- Personal Information -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title" style="display:flex;align-items:center;gap:8px"><?= icon('users', 18) ?> Personal Information</h3>
    </div>
    <div class="card-body">
      <div class="form-group">
        <label class="form-label">First Name <span class="required">*</span></label>
        <input type="text" id="pFirstName" class="form-input" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Middle Name</label>
        <input type="text" id="pMiddleName" class="form-input" value="<?= htmlspecialchars($user['middle_name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Last Name <span class="required">*</span></label>
        <input type="text" id="pLastName" class="form-input" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Email Address <span class="required">*</span></label>
        <input type="email" id="pEmail" class="form-input" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Username</label>
        <input type="text" class="form-input" value="<?= htmlspecialchars($user['username'] ?? '') ?>" disabled>
        <span class="form-hint">Username cannot be changed.</span>
      </div>
      <div class="form-group">
        <label class="form-label">Role(s)</label>
        <input type="text" class="form-input" value="<?= htmlspecialchars(implode(', ', array_column($user['roles'] ?? [], 'name'))) ?>" disabled>
      </div>
      <button class="btn btn-primary" onclick="saveProfile()" id="saveProfileBtn">Save Changes</button>
    </div>
  </div>

  <!-- Change Password -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title" style="display:flex;align-items:center;gap:8px"><?= icon('settings', 18) ?> Change Password</h3>
    </div>
    <div class="card-body">
      <div class="alert alert-info" style="margin-bottom:16px;font-size:.875rem">
        Leave these fields blank if you don't want to change your password.
      </div>
      <form onsubmit="savePassword();return false;" autocomplete="off">
      <div class="form-group">
        <label class="form-label">Current Password</label>
        <input type="password" id="pCurrentPass" class="form-input" placeholder="Enter current password" autocomplete="current-password">
      </div>
      <div class="form-group">
        <label class="form-label">New Password</label>
        <input type="password" id="pNewPass" class="form-input" placeholder="At least 8 characters" autocomplete="new-password">
      </div>
      <div class="form-group">
        <label class="form-label">Confirm New Password</label>
        <input type="password" id="pConfirmPass" class="form-input" placeholder="Repeat new password" autocomplete="new-password">
      </div>
      <button type="submit" class="btn btn-primary" id="savePassBtn">Update Password</button>
      </form>
    </div>
  </div>

</div>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
let _pendingProfileImageFile = null;
let _originalProfileImageSrc = '';
document.addEventListener('DOMContentLoaded', () => { _originalProfileImageSrc = document.getElementById('profileImgPreview').src; });

function handleProfileImageChange(event) {
  const file = event.target.files?.[0];
  if (!file) return;
  _pendingProfileImageFile = file;
  document.getElementById('profileImgPreview').src = URL.createObjectURL(file);
  document.getElementById('profileImgActions').style.display = '';
}

function cancelProfileImageChange() {
  _pendingProfileImageFile = null;
  document.getElementById('profileImgPreview').src = _originalProfileImageSrc;
  document.getElementById('profileImgActions').style.display = 'none';
  document.getElementById('profileImageInput').value = '';
}

async function uploadProfileImage() {
  if (!_pendingProfileImageFile) return;
  const btn = document.getElementById('saveImgBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>';
  const formData = new FormData();
  formData.append('profile_image', _pendingProfileImageFile);
  try {
    const res  = await fetch('/api/v1/profile/image', { method: 'POST', headers: { 'X-CSRF-Token': csrf }, body: formData });
    const data = await res.json();
    if (data.success) {
      showToast('Profile photo updated!', 'success');
      _originalProfileImageSrc = appPath(data.data.profile_image);
      document.getElementById('profileImgPreview').src = _originalProfileImageSrc;
      document.getElementById('profileImgActions').style.display = 'none';
      const topbarAvatar = document.getElementById('topbarAvatar');
      if (topbarAvatar) topbarAvatar.innerHTML = `<img src="${_originalProfileImageSrc}" alt="Profile" style="width:100%;height:100%;object-fit:cover;border-radius:50%" onerror="this.parentElement.innerHTML='<?= strtoupper(substr($_SESSION['user']['first_name'] ?? 'U', 0, 1)) ?>'">`;
    } else {
      showToast(data.message || 'Error uploading photo', 'error');
      cancelProfileImageChange();
    }
  } catch (e) { showToast('Network error', 'error'); cancelProfileImageChange(); }
  btn.disabled = false; btn.innerHTML = 'Save Photo';
}

async function saveProfile() {
  const btn = document.getElementById('saveProfileBtn');
  btn.disabled = true;
  const payload = {
    first_name:  document.getElementById('pFirstName').value.trim(),
    middle_name: document.getElementById('pMiddleName').value.trim(),
    last_name:   document.getElementById('pLastName').value.trim(),
    email:       document.getElementById('pEmail').value.trim(),
  };
  if (!payload.first_name || !payload.last_name || !payload.email) {
    showToast('First name, last name, and email are required', 'error');
    btn.disabled = false;
    return;
  }
  try {
    const res  = await fetch('/api/v1/profile', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify(payload),
    });
    const data = await res.json();
    if (data.success) showToast('Profile updated successfully', 'success');
    else showToast(data.message || 'Error saving profile', 'error');
  } catch (e) { showToast('Network error', 'error'); }
  btn.disabled = false;
}

async function savePassword() {
  const btn      = document.getElementById('savePassBtn');
  const current  = document.getElementById('pCurrentPass').value;
  const newPass  = document.getElementById('pNewPass').value;
  const confirm  = document.getElementById('pConfirmPass').value;

  if (!current) { showToast('Please enter your current password', 'error'); return; }
  if (newPass.length < 8) { showToast('New password must be at least 8 characters', 'error'); return; }
  if (newPass !== confirm) { showToast('New passwords do not match', 'error'); return; }

  btn.disabled = true;
  try {
    const res  = await fetch('/api/v1/profile', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify({ current_password: current, new_password: newPass }),
    });
    const data = await res.json();
    if (data.success) {
      showToast('Password updated successfully', 'success');
      document.getElementById('pCurrentPass').value = '';
      document.getElementById('pNewPass').value     = '';
      document.getElementById('pConfirmPass').value = '';
    } else {
      showToast(data.message || 'Error updating password', 'error');
    }
  } catch (e) { showToast('Network error', 'error'); }
  btn.disabled = false;
}
</script>

<?php
$content   = ob_get_clean();
$title     = 'Profile Settings | Dream Blanks POS';
$pageTitle = 'Profile Settings';
require VIEW_PATH . '/layouts/main.php';
?>
