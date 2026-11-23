document.addEventListener('click',e=>{const open=e.target.closest('[data-dialog]');if(open)document.getElementById(open.dataset.dialog)?.showModal();if(e.target.closest('[data-close]'))e.target.closest('dialog')?.close();});
document.querySelectorAll('form').forEach(form=>form.addEventListener('submit',()=>{form.setAttribute('aria-busy','true');}));
