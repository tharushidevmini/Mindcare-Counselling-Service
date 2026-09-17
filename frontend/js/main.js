function createToastWrap() {
  let wrap = document.getElementById('toast-wrap');
  if (!wrap) {
    wrap = document.createElement('div');
    wrap.id = 'toast-wrap';
    wrap.style.cssText = 'position:fixed;bottom:2rem;right:2rem;z-index:9999;display:flex;flex-direction:column;gap:.6rem;';
    document.body.appendChild(wrap);
  }
  return wrap;
}

function showToast(message, type = 'info', duration = 4000) {
  const wrap = createToastWrap();
  const config = {
    success: { bg:'#F0FAF4', border:'#2D9B6A', text:'#1A5C38', iconBg:'#2D9B6A', icon:'✓' },
    error:   { bg:'#FFF5F5', border:'#C53030', text:'#C53030', iconBg:'#C53030', icon:'✕' },
    warning: { bg:'#FFFBEB', border:'#D97706', text:'#92400E', iconBg:'#D97706', icon:'!' },
    info:    { bg:'#EFF6FF', border:'#2563EB', text:'#1E40AF', iconBg:'#2563EB', icon:'i' },
  };
  const c = config[type] || config.info;
  const t = document.createElement('div');
  t.style.cssText = `
    display:flex;
    align-items:center;
    gap:.75rem;
    padding:.875rem 1.1rem;
    background:${c.bg};
    border:1.5px solid ${c.border};
    border-left:4px solid ${c.border};
    border-radius:12px;
    width:320px;
    box-shadow:0 4px 16px rgba(0,0,0,0.10);
    animation:toastIn .3s cubic-bezier(.21,1.02,.73,1) forwards;
    font-family:inherit;
  `;
  t.innerHTML = `
    <div style="
      width:24px;height:24px;
      border-radius:50%;
      background:${c.iconBg};
      color:white;
      display:flex;align-items:center;justify-content:center;
      font-size:12px;font-weight:800;
      flex-shrink:0;
    ">${c.icon}</div>
    <span style="
      flex:1;
      font-size:13.5px;
      font-weight:500;
      color:${c.text};
      line-height:1.45;
    ">${message}</span>
    <button onclick="this.closest('div').remove()" style="
      background:none;border:none;
      cursor:pointer;
      color:${c.border};
      font-size:18px;
      padding:0;line-height:1;
      flex-shrink:0;
      opacity:.5;
      margin-left:.25rem;
    ">×</button>
  `;
  wrap.appendChild(t);
  setTimeout(() => {
    t.style.animation = 'toastOut .3s ease forwards';
    setTimeout(() => t.remove(), 300);
  }, duration);
}

const toast = {
  success: (msg, d) => showToast(msg, 'success', d),
  error:   (msg, d) => showToast(msg, 'error',   d),
  info:    (msg, d) => showToast(msg, 'info',     d),
  warning: (msg, d) => showToast(msg, 'warning',  d),
};

const _s = document.createElement('style');
_s.textContent = `
  @keyframes toastIn  { from { transform:translateX(110%); opacity:0; } to { transform:translateX(0); opacity:1; } }
  @keyframes toastOut { from { transform:translateX(0); opacity:1; } to { transform:translateX(110%); opacity:0; } }
`;
document.head.appendChild(_s);