(function ($, window, document) {
    'use strict';

    const config = window.bwProductImportAdmin || { strings: {} };
    const strings = config.strings || {};
    const prompts = config.prompts || {};

    function getString(key, fallback) {
        return strings[key] || fallback;
    }

    function normalizeHeader(value) {
        return String(value || '')
            .toLowerCase()
            .trim()
            .replace(/[\s_\-\/]+/g, '');
    }

    function findHeaderKey(fields, candidates) {
        const normalizedMap = {};
        (fields || []).forEach(function (field) {
            normalizedMap[normalizeHeader(field)] = field;
        });

        for (let i = 0; i < candidates.length; i++) {
            if (normalizedMap[candidates[i]]) {
                return normalizedMap[candidates[i]];
            }
        }

        return '';
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function buildStatCard(label, value) {
        return [
            '<div style="padding:14px 16px; border:1px solid #e7e7e7; border-radius:12px; background:#fafafa;">',
                '<div style="font-size:12px; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; margin-bottom:6px;">', escapeHtml(label), '</div>',
                '<div style="font-size:28px; line-height:1; font-weight:700;">', escapeHtml(value), '</div>',
            '</div>'
        ].join('');
    }

    function sanitizeRows(parsed) {
        const fields = Array.isArray(parsed.meta && parsed.meta.fields) ? parsed.meta.fields : [];
        const skuKey = findHeaderKey(fields, ['sku', 'productsku']);
        const nameKey = findHeaderKey(fields, ['name', 'productname', 'title', 'producttitle', 'posttitle']);
        const rowTypeKey = findHeaderKey(fields, ['rowtype', 'row_type']);
        const parentSkuKey = findHeaderKey(fields, ['parentsku', 'parent_sku']);
        const variationNameKey = findHeaderKey(fields, ['variationname', 'variation_name']);
        const validRows = [];
        const invalidRows = [];

        (parsed.data || []).forEach(function (row, index) {
            const sku = skuKey ? String(row[skuKey] || '').trim() : '';
            const name = nameKey ? String(row[nameKey] || '').trim() : '';
            const rowType = rowTypeKey ? String(row[rowTypeKey] || '').trim().toLowerCase() : '';
            const parentSku = parentSkuKey ? String(row[parentSkuKey] || '').trim() : '';
            const variationName = variationNameKey ? String(row[variationNameKey] || '').trim() : '';

            if (rowType === 'variation') {
                if (sku !== '' && parentSku !== '' && variationName !== '') {
                    validRows.push(row);
                    return;
                }

                invalidRows.push({
                    rowNumber: index + 2,
                    reason: getString('missingVariationIdentifiers', 'Missing variation SKU, parent SKU, or variation name')
                });
                return;
            }

            if (sku !== '' || name !== '') {
                validRows.push(row);
                return;
            }

            invalidRows.push({
                rowNumber: index + 2,
                reason: getString('missingSkuAndName', 'Missing SKU and name')
            });
        });

        return {
            fields: fields,
            skuKey: skuKey,
            nameKey: nameKey,
            validRows: validRows,
            invalidRows: invalidRows
        };
    }

    function createSanitizedFile(file, fields, validRows) {
        const csvText = window.Papa.unparse({
            fields: fields,
            data: validRows
        });

        const blob = new Blob(["\uFEFF" + csvText], { type: 'text/csv;charset=utf-8' });
        return new File([blob], file.name, { type: 'text/csv' });
    }

    function fallbackCopyText(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', 'readonly');
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        textarea.style.pointerEvents = 'none';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();

        try {
            document.execCommand('copy');
        } finally {
            document.body.removeChild(textarea);
        }
    }

    function copyText(text) {
        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            return navigator.clipboard.writeText(text).catch(function () {
                fallbackCopyText(text);
            });
        }

        fallbackCopyText(text);
        return Promise.resolve();
    }

    $(function () {
        const $form = $('#bw-import-upload-form');
        const $fileInput = $('#bw_import_csv');
        const $modal = $('#bw-import-preflight-modal');
        const $stats = $('#bw-import-preflight-stats');
        const $warning = $('#bw-import-preflight-warning');
        const $error = $('#bw-import-preflight-error');
        const $invalidWrap = $('#bw-import-preflight-invalid');
        const $invalidList = $('#bw-import-preflight-invalid-list');
        const $continue = $('#bw-import-preflight-continue');
        const $close = $('#bw-import-preflight-close');
        const $submitButton = $('#bw_import_upload_submit');
        const $selectedFile = $('#bw-import-upload-selected-file');
        const $uploadStatus = $('#bw-import-upload-status');
        const $mappingSection = $('#bw-import-mapping-section');
        const $copyButtons = $('.bw-import-template-card__copy-prompt');
        const $copyStatus = $('#bw-import-template-copy-status');

        if (!$form.length || !$fileInput.length) {
            if ($copyButtons.length) {
                // Allow copy prompt actions even when upload UI is absent.
            } else {
                return;
            }
        }

        if ($copyButtons.length) {
            $copyButtons.on('click', function () {
                const $button = $(this);
                const promptKey = String($button.data('promptKey') || '');
                const defaultLabel = String($button.data('defaultLabel') || getString('copyPrompt', 'Copy Prompt'));
                const promptEntry = prompts[promptKey];
                const promptContent = promptEntry && typeof promptEntry.content === 'string' ? promptEntry.content : '';

                if (!promptContent) {
                    if ($copyStatus.length) {
                        $copyStatus.text(getString('copyFailed', 'Copy failed. Please try again.'));
                    }
                    return;
                }

                copyText(promptContent).then(function () {
                    $button.addClass('is-copied');
                    $button.find('span').last().text(getString('copiedPrompt', 'Copied'));

                    if ($copyStatus.length) {
                        $copyStatus.text(getString('copiedPrompt', 'Copied'));
                    }

                    window.setTimeout(function () {
                        $button.removeClass('is-copied');
                        $button.find('span').last().text(defaultLabel);
                    }, 1200);
                }).catch(function () {
                    if ($copyStatus.length) {
                        $copyStatus.text(getString('copyFailed', 'Copy failed. Please try again.'));
                    }
                });
            });
        }

        if (!$form.length || !$fileInput.length) {
            return;
        }

        let pendingSubmit = false;
        const defaultSubmitLabel = String($submitButton.val() || $submitButton.data('defaultLabel') || getString('defaultUploadCta', 'Upload & Analyze'));

        function setUploadStatus(message, type) {
            if (!$uploadStatus.length) {
                return;
            }

            $uploadStatus
                .removeClass('is-info is-success is-error')
                .text(message || '');

            if (type) {
                $uploadStatus.addClass('is-' + type);
            }
        }

        function setSelectedFileMessage(file) {
            if (!$selectedFile.length) {
                return;
            }

            if (file && file.name) {
                $selectedFile.text(getString('selectedFilePrefix', 'Selected file: ') + file.name).prop('hidden', false);
                setUploadStatus(getString('fileSelectedStatus', 'File selected. Click Upload & Analyze to continue.'), 'success');
            } else {
                $selectedFile.text('').prop('hidden', true);
                setUploadStatus('', '');
            }
        }

        function setSubmitButtonState(label, disabled) {
            if (!$submitButton.length) {
                return;
            }

            $submitButton.val(label);
            $submitButton.prop('disabled', !!disabled).attr('aria-disabled', disabled ? 'true' : 'false');
        }

        function resetUploadButton() {
            setSubmitButtonState(defaultSubmitLabel, false);
        }

        function closeModal() {
            $modal.hide();
            $warning.hide().text('');
            $error.hide().text('');
            $invalidWrap.hide();
            $invalidList.empty();
            $stats.empty();
            $continue.prop('disabled', false).text(getString('continueImport', 'Continue to mapping'));
            $close.prop('disabled', false);
        }

        function openModal(summary) {
            $stats.html([
                buildStatCard(getString('totalRows', 'Total rows'), summary.totalRows),
                buildStatCard(getString('validRows', 'Valid rows'), summary.validRows),
                buildStatCard(getString('invalidRows', 'Invalid rows'), summary.invalidRows)
            ].join(''));

            if (summary.parserWarnings > 0) {
                $warning.text(
                    getString('invalidRowsWarning', 'Some rows are malformed and will be skipped. The import will continue with the valid rows only.') +
                    ' ' +
                    getString('parserWarnings', 'Parser warnings') +
                    ': ' + summary.parserWarnings
                ).show();
            } else if (summary.invalidRows > 0) {
                $warning.text(getString('invalidRowsWarning', 'Some rows are malformed and will be skipped. The import will continue with the valid rows only.')).show();
            }

            if (summary.invalidExamples.length) {
                summary.invalidExamples.forEach(function (item) {
                    $invalidList.append('<li>Row ' + escapeHtml(item.rowNumber) + ': ' + escapeHtml(item.reason) + '</li>');
                });
                $invalidWrap.show();
            }

            if (summary.validRows > 0) {
                $continue.show().prop('disabled', false);
                $error.hide().text('');
            } else {
                $continue.hide().prop('disabled', true);
                $error.text(getString('allRowsInvalid', 'No valid product rows were found in this CSV. Please check the file and try again.')).show();
            }

            $modal.css('display', 'flex');
        }

        function submitOriginalFile(statusMessage) {
            setUploadStatus(statusMessage || getString('uploadingCsv', 'Uploading CSV...'), 'info');
            setSubmitButtonState(getString('uploadingCsvCta', 'Uploading CSV...'), true);
            pendingSubmit = true;
            $form.trigger('submit');
        }

        if ($mappingSection.length) {
            window.setTimeout(function () {
                $mappingSection.get(0).scrollIntoView({ behavior: 'smooth', block: 'start' });
                $mappingSection.trigger('focus');
            }, 120);
        }

        $fileInput.on('change', function () {
            const file = this.files && this.files[0] ? this.files[0] : null;
            resetUploadButton();
            closeModal();
            setSelectedFileMessage(file);
        });

        $close.on('click', function () {
            closeModal();
            resetUploadButton();
            setSelectedFileMessage($fileInput.get(0).files && $fileInput.get(0).files[0] ? $fileInput.get(0).files[0] : null);
        });

        $continue.on('click', function () {
            $continue.prop('disabled', true).text(getString('uploadingCsv', 'Uploading CSV...'));
            $close.prop('disabled', true);
            submitOriginalFile(getString('uploadingCsv', 'Uploading CSV...'));
        });

        $modal.on('click', function (event) {
            if (event.target === $modal.get(0)) {
                closeModal();
                resetUploadButton();
                setSelectedFileMessage($fileInput.get(0).files && $fileInput.get(0).files[0] ? $fileInput.get(0).files[0] : null);
            }
        });

        $form.on('submit', function (event) {
            if (pendingSubmit) {
                pendingSubmit = false;
                return true;
            }

            const file = $fileInput.get(0).files && $fileInput.get(0).files[0];
            if (!file || typeof window.Papa === 'undefined' || typeof window.Papa.parse !== 'function') {
                if (file) {
                    submitOriginalFile(getString('uploadingCsv', 'Uploading CSV...'));
                    return false;
                }
                return true;
            }

            event.preventDefault();
            closeModal();
            setSubmitButtonState(getString('analyzingCsv', 'Analyzing CSV...'), true);
            setUploadStatus(getString('analyzingCsvStatus', 'Analyzing CSV in your browser. A preflight check will open before upload.'), 'info');

            window.Papa.parse(file, {
                header: true,
                skipEmptyLines: true,
                complete: function (results) {
                    try {
                        const sanitized = sanitizeRows(results);

                        if (sanitized.validRows.length > 0) {
                            const replacementFile = createSanitizedFile(file, sanitized.fields, sanitized.validRows);
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(replacementFile);
                            $fileInput.get(0).files = dataTransfer.files;
                        }

                        openModal({
                            totalRows: (results.data || []).length,
                            validRows: sanitized.validRows.length,
                            invalidRows: sanitized.invalidRows.length,
                            parserWarnings: Array.isArray(results.errors) ? results.errors.length : 0,
                            invalidExamples: sanitized.invalidRows.slice(0, 5)
                        });
                        resetUploadButton();
                        setUploadStatus(getString('preflightReadyStatus', 'Preflight complete. Review the summary, then continue to upload and open the mapping step.'), 'success');
                    } catch (parseError) {
                        console.error(parseError);
                        submitOriginalFile(getString('uploadingCsv', 'Uploading CSV...'));
                    }
                },
                error: function (error) {
                    console.error(error);
                    submitOriginalFile(getString('uploadingCsv', 'Uploading CSV...'));
                }
            });

            return false;
        });
    });
})(jQuery, window, document);
