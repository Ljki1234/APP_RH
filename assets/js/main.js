

document.addEventListener('DOMContentLoaded', function() {
  
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });


    const salaireForm = document.getElementById('salaireForm');
    if (salaireForm) {
        const salaireBase = document.getElementById('salaire_base');
        const prime = document.getElementById('prime');
        const montantHeuresSup = document.getElementById('montant_heures_sup');
        const retenues = document.getElementById('retenues');
        const salaireNet = document.getElementById('salaire_net');
        const employeSelect = document.getElementById('employe_id');

        function calculateSalaireNet() {
            const base = parseFloat(salaireBase.value) || 0;
            const primeValue = parseFloat(prime.value) || 0;
            const heuresSupValue = parseFloat(montantHeuresSup.value) || 0;
            const retenuesValue = parseFloat(retenues.value) || 0;
            
            const net = base + primeValue + heuresSupValue - retenuesValue;
            salaireNet.value = net.toFixed(2);
        }

        if (salaireBase) salaireBase.addEventListener('input', calculateSalaireNet);
        if (prime) prime.addEventListener('input', calculateSalaireNet);
        if (montantHeuresSup) montantHeuresSup.addEventListener('input', calculateSalaireNet);
        if (retenues) retenues.addEventListener('input', calculateSalaireNet);

        if (employeSelect) {
            employeSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const salaireBaseValue = selectedOption.getAttribute('data-salaire');
                if (salaireBaseValue && salaireBase) {
                    salaireBase.value = salaireBaseValue;
                    calculateSalaireNet();
                }
            });
        }
    }


    const congeForm = document.getElementById('congeForm');
    if (congeForm) {
        const dateDebut = congeForm.querySelector('input[name="date_debut"]');
        const dateFin = congeForm.querySelector('input[name="date_fin"]');
        
        if (dateDebut && dateFin) {
            function calculateDays() {
                if (dateDebut.value && dateFin.value) {
                    const debut = new Date(dateDebut.value);
                    const fin = new Date(dateFin.value);
                    
                    if (fin < debut) {
                        alert('La date de fin doit être après la date de début');
                        dateFin.value = '';
                        return;
                    }
                    
                    const diffTime = Math.abs(fin - debut);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                    

                    console.log('Nombre de jours:', diffDays);
                }
            }
            
            dateDebut.addEventListener('change', calculateDays);
            dateFin.addEventListener('change', calculateDays);
        }
    }


    // Modal de confirmation de suppression (global)
    const deleteButtons = document.querySelectorAll(
        'a[href*="action=delete"], a[href*="do=delete"], a.js-confirm-delete'
    );
    const confirmModalEl = document.getElementById('confirmDeleteModal');
    const confirmMessageEl = document.getElementById('confirmDeleteMessage');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    let targetDeleteHref = null;

    if (confirmModalEl && confirmMessageEl && confirmBtn && deleteButtons.length > 0) {
        const bsConfirmModal = new bootstrap.Modal(confirmModalEl);

        deleteButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const message = button.getAttribute('data-confirm')
                    || 'Êtes-vous sûr de vouloir supprimer cet élément ?';
                confirmMessageEl.textContent = message;
                targetDeleteHref = button.getAttribute('href');
                bsConfirmModal.show();
            });
        });

        confirmBtn.addEventListener('click', function() {
            if (targetDeleteHref) {
                window.location.href = targetDeleteHref;
            }
        });
    }
});
