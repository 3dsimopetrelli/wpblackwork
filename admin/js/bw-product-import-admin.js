(function ($, window, document) {
    'use strict';

    const config = window.bwProductImportAdmin || { strings: {} };
    const strings = config.strings || {};

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
        const validRows = [];
        const invalidRows = [];

        (parsed.data || []).forEach(function (row, index) {
            const sku = skuKey ? String(row[skuKey] || '').trim() : '';
            const name = nameKey ? String(row[nameKey] || '').trim() : '';

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

        if (!$form.length || !$fileInput.length) {
            return;
        }

        let pendingSubmit = false;

        function closeModal() {
            $modal.hide();
            $warning.hide().text('');
            $error.hide().text('');
            $invalidWrap.hide();
            $invalidList.empty();
            $stats.empty();
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

        function submitOriginalFile() {
            pendingSubmit = true;
            $form.trigger('submit');
        }

        $close.on('click', function () {
            closeModal();
        });

        $continue.on('click', function () {
            closeModal();
            pendingSubmit = true;
            $form.trigger('submit');
        });

        $modal.on('click', function (event) {
            if (event.target === $modal.get(0)) {
                closeModal();
            }
        });

        $form.on('submit', function (event) {
            if (pendingSubmit) {
                pendingSubmit = false;
                return true;
            }

            const file = $fileInput.get(0).files && $fileInput.get(0).files[0];
            if (!file || typeof window.Papa === 'undefined' || typeof window.Papa.parse !== 'function') {
                return true;
            }

            event.preventDefault();
            closeModal();

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
                    } catch (parseError) {
                        console.error(parseError);
                        pendingSubmit = true;
                        $form.trigger('submit');
                    }
                },
                error: function (error) {
                    console.error(error);
                    pendingSubmit = true;
                    $form.trigger('submit');
                }
            });

            return false;
        });
    });
})(jQuery, window, document);
