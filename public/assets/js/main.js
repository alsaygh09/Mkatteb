(function () {
    var burger = document.getElementById('burgerBtn');
    var nav = document.querySelector('.main-nav');

    if (burger && nav) {
        burger.addEventListener('click', function () {
            var isOpen = nav.classList.toggle('open');
            burger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    var dropdowns = document.querySelectorAll('.js-dropdown');

    function closeDropdown(dropdown) {
        if (!dropdown) return;

        dropdown.classList.remove('is-open');
        var toggle = dropdown.querySelector('.dropdown-toggle');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    }

    function closeAllDropdowns(exceptDropdown) {
        dropdowns.forEach(function (dropdown) {
            if (dropdown !== exceptDropdown) {
                closeDropdown(dropdown);
            }
        });
    }

    dropdowns.forEach(function (dropdown) {
        var toggle = dropdown.querySelector('.dropdown-toggle');
        if (!toggle) return;

        toggle.addEventListener('click', function () {
            var expanded = toggle.getAttribute('aria-expanded') === 'true';
            closeAllDropdowns(dropdown);

            dropdown.classList.toggle('is-open', !expanded);
            toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        });
    });

    document.addEventListener('click', function (event) {
        dropdowns.forEach(function (dropdown) {
            if (!dropdown.contains(event.target)) {
                closeDropdown(dropdown);
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;

        dropdowns.forEach(function (dropdown) {
            var toggle = dropdown.querySelector('.dropdown-toggle');
            var wasOpen = dropdown.classList.contains('is-open');
            closeDropdown(dropdown);

            if (wasOpen && toggle) {
                toggle.focus();
            }
        });
    });

    function getConfirmMessage(element) {
        if (!element) return '';

        var message = element.getAttribute('data-confirm') || '';
        if (!message) return '';

        var fieldName = element.getAttribute('data-confirm-field');
        var expectedValue = element.getAttribute('data-confirm-value');

        if (fieldName && expectedValue !== null) {
            var field = element.querySelector('[name="' + fieldName + '"]');
            if (!field || field.value !== expectedValue) {
                return '';
            }
        }

        return message;
    }

    document.addEventListener('click', function (event) {
        var target = event.target.closest('[data-confirm]');
        if (!target || target.tagName === 'FORM') return;

        var message = getConfirmMessage(target);
        if (!message) return;

        if (!window.confirm(message)) {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }

        if (target.form) {
            target.setAttribute('data-confirmed', 'true');
        }
    });

    document.addEventListener('submit', function (event) {
        var form = event.target;
        var submitter = event.submitter || null;
        var message = getConfirmMessage(form);

        if (!message && submitter) {
            message = getConfirmMessage(submitter);

            if (message && submitter.getAttribute('data-confirmed') === 'true') {
                submitter.removeAttribute('data-confirmed');
                return;
            }
        }

        if (message && !window.confirm(message)) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    });

    document.querySelectorAll('.js-auto-submit input, .js-auto-submit select').forEach(function (field) {
        field.addEventListener('change', function () {
            if (field.form) {
                field.form.submit();
            }
        });
    });

    document.querySelectorAll('[data-price-slider]').forEach(function (slider) {
        var outputSelector = slider.getAttribute('data-output');
        var output = outputSelector ? document.querySelector(outputSelector) : null;
        var currency = slider.getAttribute('data-currency') || 'BD';

        function updatePriceOutput() {
            if (!output) return;

            var value = parseFloat(slider.value);
            if (Number.isNaN(value)) {
                value = 0;
            }

            output.textContent = 'Up to ' + currency + ' ' + value.toFixed(3);
        }

        slider.addEventListener('input', updatePriceOutput);
        updatePriceOutput();
    });

    var dropZone = document.querySelector('.drop-zone');
    var fileInput = document.getElementById('bookImagesInput');
    var preview = document.querySelector('[data-image-preview]');

    function renderPreview(files) {
        if (!preview) return;
        preview.innerHTML = '';

        Array.prototype.slice.call(files, 0, 5).forEach(function (file) {
            if (!file.type || file.type.indexOf('image/') !== 0) return;

            var img = document.createElement('img');
            img.alt = file.name;
            img.src = URL.createObjectURL(file);
            img.onload = function () {
                URL.revokeObjectURL(img.src);
            };
            preview.appendChild(img);
        });
    }

    if (dropZone && fileInput) {
        ['dragenter', 'dragover'].forEach(function (eventName) {
            dropZone.addEventListener(eventName, function (event) {
                event.preventDefault();
                dropZone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach(function (eventName) {
            dropZone.addEventListener(eventName, function (event) {
                event.preventDefault();
                dropZone.classList.remove('is-dragover');
            });
        });

        dropZone.addEventListener('drop', function (event) {
            if (event.dataTransfer && event.dataTransfer.files.length) {
                fileInput.files = event.dataTransfer.files;
                renderPreview(fileInput.files);
            }
        });

        fileInput.addEventListener('change', function () {
            renderPreview(fileInput.files);
        });
    }
})();
