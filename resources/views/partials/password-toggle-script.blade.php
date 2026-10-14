<script>
document.addEventListener('click', function(e){const btn=e.target.closest('[data-password-toggle]');if(!btn)return;const wrap=btn.closest('.relative');const input=wrap?.querySelector('input');if(!input)return;input.type=input.type==='password'?'text':'password';const icon=btn.querySelector('i');if(icon)icon.className=input.type==='password'?'fas fa-eye':'fas fa-eye-slash';});
</script>
