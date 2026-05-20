/*==============================================================*/
// Contact Form  JS
/*==============================================================*/
(function ($) {
    "use strict"; // Start of use strict
    $("#contactForm").validator().on("submit", function (event) {
        if (event.isDefaultPrevented()) {
            // handle the invalid form...
            formError();
            submitMSG(false, "Por favor complete todos los campos requeridos correctamente.");
        } else {
            // everything looks good!
            event.preventDefault();
            submitForm();
        }
    });


    function submitForm(){
        $.ajax({
            type: "POST",
            url: "assets/php/form-process.php",
            data: $("#contactForm").serialize(),
            success : function(text){
                if (text == "success"){
                    formSuccess();
                } else {
                    formError();
                    submitMSG(false,text);
                }
            }
        });
    }

    function formSuccess(){
        $("#contactForm")[0].reset();
        submitMSG(true, "Mensaje enviado exitosamente.");
        
        // Mostrar mensaje con SweetAlert si está disponible
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Mensaje enviado',
                text: 'Tu mensaje ha sido enviado correctamente. Nos pondremos en contacto contigo pronto.',
                confirmButtonText: 'Aceptar'
            });
        }
    }

    function formError(){
        $("#contactForm").removeClass().addClass('shake animated').one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function(){
            $(this).removeClass();
        });
    }

    function submitMSG(valid, msg){
        if(valid){
            var msgClasses = "h4 tada animated text-success";
        } else {
            var msgClasses = "h4 text-danger";
        }
        $("#msgSubmit").removeClass().addClass(msgClasses).text(msg);
    }
}(jQuery)); // End of use strict
