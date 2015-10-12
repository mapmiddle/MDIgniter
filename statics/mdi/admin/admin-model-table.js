
$(document).ready(function(){
   $('.model-table .table .link').click(function(){
       var href = $(this).data('href');
       if (href) {
           window.document.location = $(this).data('href');
           return false;
       }

       return true;
   });

    $('.model-table .table .row-checkbox-all').click(function(e){
        var chk = $(this).is(":checked");
        var $rowCheckBox = $(".model-table .table .row-checkbox");
        if(chk) {
            $rowCheckBox.each(function(){
                this.checked = true;
            });
        } else {
            $rowCheckBox.each(function(){
                this.checked = false;
            });
        }
    });

    $('.model-table .table .row-checkbox').click(function(e){
        e.stopPropagation();
    });

    $('#btn-delete').click(function(e){
        e.preventDefault();

        var deleteList = [];
        var href = $(this).attr('href');

        $(".model-table .table .row-checkbox:checked").each(function(){
            deleteList.push($(this).data('id'));
        });

        if (deleteList.length == 0) {
            alert("No items selected");///
        } else {
            var $form = $("<form/>", {
                action: href,
                method: 'post'
            });

            $(deleteList).each(function(key, value){
                $form.append(
                    $("<input/>", {
                        name: 'deleteIds[]',
                        value: value
                    })
                );
            });

            $form.submit();
        }
    });
});