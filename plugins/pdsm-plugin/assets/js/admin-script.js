jQuery(document).ready(function($) {
    $('.pdsm-diagnose').on('click', function() {
        var domain = $(this).data('domain');
        var $btn = $(this);
        $btn.prop('disabled', true).text('Diagnosticando...');

        $.ajax({
            url: pdsm_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'pdsm_ajax_diagnose',
                domain: domain,
                nonce: pdsm_ajax.nonce
            },
            success: function(response) {
                alert('Diagnóstico concluído. Veja os logs.');
                location.reload();
            },
            error: function() {
                alert('Erro ao diagnosticar.');
                $btn.prop('disabled', false).text('Diagnosticar');
            }
        });
    });

    $('.pdsm-heal').on('click', function() {
        var domain = $(this).data('domain');
        var $btn = $(this);
        $btn.prop('disabled', true).text('Curando...');

        $.ajax({
            url: pdsm_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'pdsm_ajax_heal',
                domain: domain,
                auto: true,
                nonce: pdsm_ajax.nonce
            },
            success: function(response) {
                alert('Auto-cura executada.');
                location.reload();
            },
            error: function() {
                alert('Erro na auto-cura.');
                $btn.prop('disabled', false).text('Auto-Curar');
            }
        });
    });
});
