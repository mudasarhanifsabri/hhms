(function () {
  var MAX_WIDTH = 1280;
  var QUALITY = 0.78;

  document.addEventListener('change', function (event) {
    var input = event.target.closest('[data-tenant-upload]');
    if (!input) {
      return;
    }

    handleTenantImages(input);
  });

  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form.matches('[data-tenant-inspection-form]')) {
      return;
    }

    var pendingInputs = Array.prototype.filter.call(form.querySelectorAll('[data-tenant-upload]'), function (input) {
      return input.dataset.processing === '1';
    });

    if (!pendingInputs.length) {
      form.querySelectorAll('button[type="submit"]').forEach(function (button) {
        button.disabled = true;
        button.textContent = 'Uploading...';
      });
      return;
    }

    event.preventDefault();
    var submitButton = form.querySelector('button[type="submit"]');
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = 'Preparing photos...';
    }

    Promise.all(pendingInputs.map(function (input) {
      return input._tenantProcessing || Promise.resolve();
    })).then(function () {
      if (submitButton) {
        submitButton.textContent = 'Uploading...';
      }
      form.requestSubmit ? form.requestSubmit() : form.submit();
    });
  });

  function handleTenantImages(input) {
    var preview = input.closest('.tenant-inspect-item')?.querySelector('[data-tenant-upload-preview]');
    var files = Array.prototype.slice.call(input.files || []);

    if (!files.length) {
      updatePreview(preview, '');
      return;
    }

    updatePreview(preview, 'Preparing ' + files.length + ' photo(s)...');
    input.dataset.processing = '1';

    input._tenantProcessing = Promise.all(files.map(compressImageFile))
      .then(function (processedFiles) {
        var transfer = new DataTransfer();
        processedFiles.forEach(function (file) {
          transfer.items.add(file);
        });
        input.files = transfer.files;
        input.dataset.processing = '0';
        updatePreview(preview, processedFiles.length + ' compressed photo(s) ready to upload ✓');
      })
      .catch(function () {
        input.dataset.processing = '0';
        updatePreview(preview, files.length + ' photo(s) ready to upload');
      });
  }

  function compressImageFile(file) {
    if (!file.type || file.type.indexOf('image/') !== 0 || file.type === 'image/webp' && file.size < 900000) {
      return Promise.resolve(file);
    }

    return loadImage(file)
      .then(function (image) {
        var scale = Math.min(1, MAX_WIDTH / image.width);
        var width = Math.max(1, Math.round(image.width * scale));
        var height = Math.max(1, Math.round(image.height * scale));
        var canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        var ctx = canvas.getContext('2d');
        ctx.drawImage(image, 0, 0, width, height);
        if (image._objectUrl) {
          URL.revokeObjectURL(image._objectUrl);
        }

        return new Promise(function (resolve) {
          canvas.toBlob(function (blob) {
            if (!blob) {
              resolve(file);
              return;
            }

            var safeName = file.name.replace(/\.[^.]+$/, '') + '.webp';
            resolve(new File([blob], safeName, { type: 'image/webp', lastModified: Date.now() }));
          }, 'image/webp', QUALITY);
        });
      })
      .catch(function () {
        return file;
      });
  }

  function loadImage(file) {
    if ('createImageBitmap' in window) {
      return createImageBitmap(file);
    }

    return new Promise(function (resolve, reject) {
      var image = new Image();
      image.onload = function () {
        resolve(image);
      };
      image.onerror = reject;
      image._objectUrl = URL.createObjectURL(file);
      image.src = image._objectUrl;
    });
  }

  function updatePreview(preview, message) {
    if (preview) {
      preview.textContent = message;
    }
  }
})();
