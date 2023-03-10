function JCElasticSmartFilter(arParams) {
    $(document).on('change', '.elastic-form input', function () {
        let $this = $(this),
            form = $this.closest('form'),
            label,
            formData = new FormData(form.get(0));
        formData.append('input',$this.attr('name'))
        BX.ajax.runComponentAction('sp:elastic.filter',
            'updateCount', { // Вызывается без постфикса Action
                mode: 'class',
                data: formData, // ключи объекта data соответствуют параметрам метода
                signedParameters: arParams,
            })
            .then(function (response) {
                form.find('.total-count').html('(' + response.data.quantities.total + ')');
                $.each(response.data.quantities.properties, function (id, arValue) {
                    $.each(arValue, function (index, value) {
                        label = form.find('[for=PROPERTY_ID_' + id + '_' + value.key.replaceAll(' ', '_').replaceAll('&quot;', '') + ']')
                        label.find('.items-count').html('(' + value.doc_count + ')')
                        if(value.doc_count == 0){
                            label.addClass('disabled')
                        }else{
                            label.removeClass('disabled')
                        }
                    })
                })
            });
        return false
    })
}
