const API = window.location.origin + '/mindcare_final/backend';

let allUsers = [];   // keep the full list so search can filter it

document.addEventListener('DOMContentLoaded', loadUsers);

async function loadUsers() {
  try {
    const res  = await fetch(`${API}/api/admin/users.php`);
    const data = await res.json();
    allUsers = data.users || [];

    // stats always reflect ALL users, not the filtered view
    let students = 0, counselors = 0, advisors = 0, disabled = 0;
    allUsers.forEach(u => {
      if (u.role === 'student')          students++;
      if (u.role === 'counselor')        counselors++;
      if (u.role === 'learning_advisor') advisors++;
      if (!u.is_active || u.is_active == 0) disabled++;
    });
    document.getElementById('count-students').textContent   = students;
    document.getElementById('count-counselors').textContent = counselors;
    document.getElementById('count-advisors').textContent   = advisors;
    document.getElementById('count-disabled').textContent   = disabled;

    renderUsers(allUsers);
  } catch (e) {
    document.getElementById('users-table').innerHTML =
      `<tr><td colspan="6" style="color:red; text-align:center;">Error: ${e.message}</td></tr>`;
  }
}

// Live search — filters by name or email as the admin types
function filterUsers() {
  const q = (document.getElementById('user-search')?.value || '').toLowerCase().trim();
  if (!q) { renderUsers(allUsers); return; }
  const filtered = allUsers.filter(u =>
    (u.full_name || '').toLowerCase().includes(q) ||
    (u.email     || '').toLowerCase().includes(q));
  renderUsers(filtered);
}

function escapeHtml(str) {
  const d = document.createElement('div');
  d.textContent = str ?? '';
  return d.innerHTML;
}

function renderUsers(list) {
  const tbody = document.getElementById('users-table');
  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:#aaa;">No matching users</td></tr>';
    return;
  }
  tbody.innerHTML = list.map(u => {
      const statusBadge = u.is_active == 1
        ? '<span style="color:green; font-weight:600;">✅ Active</span>'
        : '<span style="color:red; font-weight:600;">🚫 Disabled</span>';

      const roleBadge = {
        student:          '<span class="badge badge-beige">Student</span>',
        counselor:        '<span class="badge badge-mint">Counselor</span>',
        learning_advisor: '<span class="badge" style="background:#e0e7ff; color:#3730a3;">Advisor</span>',
        admin:            '<span class="badge badge-red">Admin</span>'
      }[u.role] || escapeHtml(u.role);

      return `<tr>
        <td><strong>${escapeHtml(u.full_name)}</strong></td>
        <td style="font-size:13px; color:#888;">${escapeHtml(u.email)}</td>
        <td>${roleBadge}</td>
        <td>${statusBadge}</td>
        <td style="font-size:12px; color:#aaa;">${escapeHtml(u.created_at?.split('T')[0] || '—')}</td>
        <td style="display:flex; gap:.4rem; flex-wrap:wrap;">
          <button class="btn btn-sm ${u.is_active == 1 ? 'btn-ghost' : 'btn-mint'}"
            onclick="toggleUser(${u.id}, ${u.is_active})">
            ${u.is_active == 1 ? '🚫 Disable' : '✅ Enable'}
          </button>
          <button class="btn btn-sm btn-ghost" onclick="resetPassword(${u.id})">🔑 Reset PW</button>
        </td>
      </tr>`;
    }).join('');
}

async function toggleUser(id, currentStatus) {
  const newStatus = currentStatus == 1 ? 0 : 1;
  const action    = newStatus == 1 ? 'enable' : 'disable';
  if (!confirm(`Are you sure you want to ${action} this user?`)) return;

  const fd = new FormData();
  fd.append('user_id',   id);
  fd.append('is_active', newStatus);

  try {
    const res  = await fetch(`${API}/api/admin/toggle_user.php`, { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
      toast.success(`User ${action}d successfully.`);
      loadUsers();
    } else {
      toast.error('Could not update: ' + (data.error || 'Please try again.'));
    }
  } catch (e) {
    toast.error('Network error: ' + e.message);
  }
}

async function resetPassword(id) {
  const user = allUsers.find(u => u.id == id);
  const name = user ? user.full_name : 'this user';
  if (!confirm(`Reset password for ${name}? A new temporary password will be emailed directly to them.`)) return;

  const fd = new FormData();
  fd.append('user_id', id);

  try {
    const res  = await fetch(`${API}/api/admin/reset_password.php`, { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
      toast.success(data.message || 'Password reset. New password emailed to the user.');
    } else {
      toast.error('Could not reset: ' + (data.error || 'Please try again.'));
    }
  } catch (e) {
    toast.error('Network error: ' + e.message);
  }
}

function showAddUserModal()  { document.getElementById('add-user-modal').style.display = 'flex'; }
function closeAddUserModal() { document.getElementById('add-user-modal').style.display = 'none'; }
document.addEventListener('keydown', (e) => {
  const m = document.getElementById('add-user-modal');
  if (e.key === 'Escape' && m && m.style.display === 'flex') closeAddUserModal();
});

async function submitNewUser() {
  const name  = document.getElementById('nu-name').value.trim();
  const email = document.getElementById('nu-email').value.trim();
  const role  = document.getElementById('nu-role').value;

  if (!name || !email) { toast.warning('Please fill all fields.'); return; }

  const fd = new FormData();
  fd.append('full_name', name);
  fd.append('email',     email);
  fd.append('role',      role);

  try {
    const res  = await fetch(`${API}/api/admin/add_user.php`, { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
      toast.success(`User created! Temporary password: ${data.temp_password}`);
      closeAddUserModal();
      loadUsers();
    } else {
      toast.error('Could not create: ' + (data.error || 'Please try again.'));
    }
  } catch (e) {
    toast.error('Network error: ' + e.message);
  }
}