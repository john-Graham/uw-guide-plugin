console.log("Editor script loaded");

//Nothing to see here at the moment, what is below is an attempt to 
//dynamicly load the guide xml tabs options based on the url field


// document.addEventListener('DOMContentLoaded', function () {
//     // Ensure ACF is loaded
//     if (typeof acf === 'undefined') {
//         console.log('ACF not loaded');
//         return;
//     }

//     acf.addAction('ready', function () {
//         var acfField = acf.getField('field_656f812a945e5');

//             console.log('ACF field found');

//             // Add event listener to the field
//             acfField.on('focus', function () {
//                 console.log('Field focused');
//             });
            
//             acfField.on('change', function () {
//                 console.log('Field blurred');
//                 console.log('ACF field value: ' +  acfField.val());

//                 var acfFieldValue = acfField.val();
//                 console.log('Field value changed to: ', acfFieldValue);

//                 // Using the Fetch API for the AJAX request
//                 fetch(adminAjax.ajaxurl, {
//                     method: 'POST',
//                     headers: {
//                         'Content-Type': 'application/x-www-form-urlencoded',
//                     },
//                     body: 'action=load_guide_xml&url=' + encodeURIComponent(acfFieldValue)
//                 })
//                 .then(response => response.json())
//                 .then(data => {
//                     // Populate the select field
//                     var selectField = document.querySelector('#acf-page-sections');
//                     if (selectField) {
//                         selectField.innerHTML = ''; // Clear existing options
//                         data.forEach(function (node) {
//                             var option = document.createElement('option');
//                             option.value = node.value;
//                             option.textContent = node.label;
//                             selectField.appendChild(option);
//                         });
//                     }
//                 })
//                 .catch(error => console.error('Error:', error));
//             });

//     });
// });

// document.addEventListener('DOMContentLoaded', function () {
//     console.log('DOM loaded');
//     // Ensure ACF is loaded
//     if (typeof acf === 'undefined') {
//         console.log('ACF not loaded');
//         return;
//     }

//     acf.addAction('ready', function() {
//         var acfField = acf.getField('field_656f812a945e5');

//         if (!acfField) {
//             console.log('ACF field not found');
//             return;
//         }

//         // Get the input element of the ACF field
//         var inputElement = acfField.$el[0].querySelector('input');

//         // Check if the input element exists
//         if (inputElement) {
//             // Attach a blur event listener to the input element
//             inputElement.addEventListener('blur', function() {
//                 var value = acfField.val();
//                 console.log('Field value when unfocused: ', value);

//                 // Your AJAX call and other logic here
//             });
//         } else {
//             console.log('Input element not found in the ACF field');
//         }
//     });
// });

// document.addEventListener('DOMContentLoaded', function () {
//     // Ensure ACF is loaded
//     if (typeof acf === 'undefined') {
//         console.log('ACF not loaded');
//         return;
//     }

//     acf.addAction('ready', function () {
//         // Replace 'your_field_name' with the actual field name
//         var inputElements = document.querySelectorAll('[data-name="url"] input');

//         inputElements.forEach(function(inputElement) {
//             inputElement.addEventListener('blur', function () {
//                 // Assuming the field is a text input
//                 var value = this.value;
//                 console.log('Field value when unfocused: ', value);

//                 // Your AJAX call and other logic here
//             });
//         });
//     });
// });


// var fldUrl = new acf.Model({
//     wait: 'ready',
//     data: {
//         color: '#ffffff'
//     },
//     events: {
//         'click .select-color':  'onClick'
//     },
//     initialize: function(){
//         this.$el = $('#url');
//     },
//     onClick: function( e, $el ){

//         // get color from data attribute on '.select-color' element
//         this.set('color', $el.data('color'));

//         // render
//         this.render();
//     },
//     render: function(){
//         // this.$el.css('background-color', this.get('color'));
//     }
// });

// // interact directly with the sidebar variable
// var color = fldUrl.get('color');