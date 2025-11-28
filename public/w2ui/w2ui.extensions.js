/**
 * w2ui Extensions
 * 
 * Bu dosya w2ui.js 2.0 versiyonuna uyumlu extension metodlarını içerir.
 * Eski versiyondan (w2ui..old.js) taşınan özel metodlar burada bulunur.
 * 
 * Kullanım: w2ui.js dosyasından sonra bu dosyayı yükleyin.
 * 
 * @version 1.0.0
 * @date 2024
 */

(function() {
    'use strict';
    w2utils.locale('tr-TR');
    let messager = function (type, title, message, delay = 1500) {
        new PNotify({
            title: title,
            text: message,
            type: type,
            styling: 'bootstrap3',
            delay: delay
        });
    }
    var isShow={
        target:null,
        value:null
    }
    function showImageOverlay(obj, value) {
        if(isShow.target == obj && isShow.value == true) {
            isShow.value = false;
            w2tooltip.hide('demo-tooltip');
            return;
        }
        isShow.target = obj;
        isShow.value = true;
        w2tooltip.show({
            align: 'center',
            position: 'top',
            name: 'demo-tooltip',
            hideOn:'click',
            anchor: obj,
            html: '<div style="padding: 10px; line-height: 150%">' +
                '    <img style="height:150px" src="' + value + '">' +
                '</div>'
        })
    }
    
    // Event listener for .show-image-overlay elements
    // Handles both click and hover events
    function initImageOverlayListeners() {
        // Use event delegation to handle dynamically added elements
        $(document).on('click', '.show-image-overlay', function(e) {
            e.preventDefault();
            var imageUrl = $(e.target).attr('data-image');
            if (imageUrl) {
                showImageOverlay(e.target, imageUrl);
            }
        });
        
    }
    
    // Initialize listeners when DOM is ready
    if (typeof jQuery !== 'undefined') {
        $(document).ready(function() {
            initImageOverlayListeners();
        });
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initImageOverlayListeners);
    } else {
        // DOM already loaded
        initImageOverlayListeners();
    }
    // w2grid sınıfına extension metodları ekle
    if (typeof w2grid !== 'undefined') {
        
        /**
         * Seçili kayıtları döndürür
         * @returns {Array} Seçili kayıtların dizisi
         */
        w2grid.prototype.getSelectedRecords = function() {
            var ret = [];
            var grid = this;
            if (this.last.selection && this.last.selection.indexes) {
                this.last.selection.indexes.forEach(function(item) {
                    if (grid.records[item]) {
                        ret.push(grid.records[item]);
                    }
                });
            }
            return ret;
        };

        /**
         * Seçili kayıtları siler
         * @returns {void}
         */
        w2grid.prototype.removeSelectedRecords = function() {
            var grid = this;
            if (!this.last.selection || !this.last.selection.indexes) {
                return;
            }
            
            // Seçili kayıtları null yap
            this.last.selection.indexes.forEach(function(item) {
                if (grid.records[item]) {
                    grid.records[item] = null;
                }
            });
            
            // null kayıtları filtrele
            grid.records = grid.records.filter(function(el) { 
                return el != null; 
            });
            
            // URL'i geçici olarak devre dışı bırak (refresh sırasında server request'i engellemek için)
            var url = (typeof this.url === 'object' ? this.url.get : this.url);
            if (typeof this.url === 'object') {
                this.url.get = null;
            } else {
                this.url = null;
            }
            
            // Grid'i yenile
            this.refresh();
            this.selectNone();
            
            // URL'i geri yükle
            var self = this;
            setTimeout(function() {
                if (typeof self.url === 'object') {
                    self.url.get = url;
                } else {
                    self.url = url;
                }
            }, 300);
        };

        /**
         * Seçili kayıtları başka bir grid'e transfer eder
         * @param {string|w2grid} grid - Hedef grid adı veya grid objesi
         * @param {Object} extrec - Ek kayıt özellikleri (varsayılan: {})
         * @returns {void}
         */
        w2grid.prototype.transferSelectedRecords = function(grid,extrec) {
            if (extrec === undefined) {
                extrec = {};
            }
            var records = this.getSelectedRecords();
            extrec.Selecteds=records.map((r,i)=> r.recid);
            //fetch url.transfer
            //append authorization header
            var headers = {
                'Authorization': this.httpHeaders.Authorization
            };
            console.log("urls",this.url);
            fetch(this.url.transfer, {
                method: 'POST',
                body: JSON.stringify(extrec),
                headers: headers
            })
            .then(response => response.json())
            .then(data => {
                if (typeof messager !== 'undefined') {
                    messager(data.success == true ? 'success' : 'warning', '', data.message)
                } else if (typeof w2ui !== 'undefined' && w2ui.notify) {
                    w2ui.notify(data.success == true ? 'success' : 'warning', data.message)
                }
                if(data.success == true) {
                    this.removeSelectedRecords();
                    this.refresh();
                    setTimeout(() => {
                        w2ui[grid].reload();
                    }, 10);
                }

            })
            .catch(error => {
                if (typeof messager !== 'undefined') {
                    messager('warning', '', error.message);
                } else if (typeof w2ui !== 'undefined' && w2ui.notify) {
                    w2ui.notify('warning', error.message);
                }
            });
            console.log(extrec);

        };

        /**
         * Arama kaydetme fonksiyonu - Popup ile arama veya rapor olarak kaydetme seçeneği sunar
         * @returns {void}
         */
        w2grid.prototype.searchSave = function() {
            var self = this;
            w2popup.open({
                title: w2utils.lang('Notification'),
                width: 400,
                height: 200,
                body: `
                <div class="w2ui-centered" style="line-height: 1.8">
                    <div>
                        ${w2utils.lang('Lütfen arama kayıt tipini seçiniz')}<br>
                        <div class="w2ui-field w2ui-span3">
                            <label>${w2utils.lang('Tipi')}:</label>
                            <div>
                                <input id="report-type">
                            </div>
                        </div>
                    </div>
                </div>`,
                actions: {
                    Ok() {
                        let selectedIndex = $('#report-type').data('selected').id
                        if (selectedIndex == null) {
                            w2alert(w2utils.lang("Please select the record type."));
                        }
                        else if (selectedIndex == "search") {
                            self.searchSaveLocal();
                        } else {
                            self.saveReportForm();
                        }
                    },
                    Cancel() {
                        w2popup.close()
                    }
                },
                onOpen(evnt) {
                    let items = [];
                    items[0] = { id: "search", text: w2utils.lang("Arama olarak kaydet") };
                    items[1] = { id: "report", text: w2utils.lang("Rapor olarak kaydet") };
                    evnt.onComplete = function () {
                        setTimeout(() => {
                            $('#report-type').w2field('list', {
                                items: items,
                                match: 'contain',
                                markSearch: true
                            })
                        }, 100)
                    }
                }
            })
        };

        /**
         * Arama kaydetme fonksiyonu - Local storage'a kaydeder (w2ui.js 2.0 uyumlu)
         * @returns {void}
         */
        w2grid.prototype.searchSaveLocal = function() {
            let value = ''
            if (this.searchSelected) {
                value = this.searchSelected.text
            }
            let ind = this.savedSearches.findIndex(s => { return s.id == this.searchSelected?.id ? true : false })
            // event before
            let edata = this.trigger('searchSave', { target: this.name, saveLocalStorage: true })
            if (edata.isCancelled === true) return
            this.message({
                width: 350,
                height: 150,
                body: `<div class="w2ui-grid-save-search">
                            <span>${w2utils.lang(ind != -1 ? 'Update Search' : 'Save New Search')}</span>
                            <input class="search-name w2ui-input" placeholder="${w2utils.lang('Search name')}">
                       </div>`,
                buttons: `
                    <button id="grid-search-cancel" class="w2ui-btn">${w2utils.lang('Cancel')}</button>
                    <button id="grid-search-save" class="w2ui-btn w2ui-btn-blue" ${String(value).trim() == '' ? 'disabled': ''}>${w2utils.lang('Save')}</button>
                `
            }).open(async (event) => {
                query(event.detail.box).find('input, button').eq(0).val(value)
                await event.complete
                query(event.detail.box).find('#grid-search-cancel').on('click', () => {
                    this.message()
                })
                query(event.detail.box).find('#grid-search-save').on('click', () => {
                    let name = query(event.detail.box).find('.w2ui-message .search-name').val()
                    // save in savedSearches
                    if (this.searchSelected && ind != -1) {
                        Object.assign(this.savedSearches[ind], {
                            id: name,
                            text: name,
                            logic: this.last.logic,
                            data: w2utils.clone(this.searchData)
                        })
                    } else {
                        this.savedSearches.push({
                            id: name,
                            text: name,
                            icon: 'w2ui-icon-search',
                            remove: true,
                            logic: this.last.logic,
                            data: this.searchData
                        })
                    }
                    // save local storage
                    this.cacheSave('searches', this.savedSearches.map(s => w2utils.clone(s, { exclude: ['remove', 'icon'] })))
                    this.message()
                    // update on screen
                    if (this.searchSelected) {
                        this.searchSelected.text = name
                        query(this.box).find(`#grid_${this.name}_search_name .name-text`).html(name)
                    } else {
                        this.searchSelected = {
                            text: name,
                            logic: this.last.logic,
                            data: w2utils.clone(this.searchData)
                        }
                        query(event.detail.box).find(`#grid_${this.name}_search_all`).val(' ').prop('readOnly', true)
                        query(event.detail.box).find(`#grid_${this.name}_search_name`).show().find('.name-text').html(name)
                    }
                    edata.finish({ name })
                })
                query(event.detail.box).find('input, button')
                    .off('.message')
                    .on('keydown.message', evt => {
                        let val = String(query(event.detail.box).find('.w2ui-message-body input').val()).trim()
                        if (evt.keyCode == 13 && val != '') {
                            query(event.detail.box).find('#grid-search-save').trigger('click') // enter
                        }
                        if (evt.keyCode == 27) { // escape
                            this.message()
                        }
                    })
                    .eq(0)
                    .on('input.message', evt => {
                        let $save = query(event.detail.box).closest('.w2ui-message').find('#grid-search-save')
                        if (String(query(event.detail.box).val()).trim() === '') {
                            $save.prop('disabled', true)
                        } else {
                            $save.prop('disabled', false)
                        }
                    })
                    .get(0)
                    .focus()
            })
        };

        /**
         * Rapor formunu kaydetme fonksiyonu (w2ui.js 2.0 uyumlu)
         * @returns {void}
         */
        w2grid.prototype.saveReportForm = function() {
            let value = ''
            if (this.searchSelected) {
                value = this.searchSelected.text
            }
            // event before
            let edata = this.trigger('reportSave', { target: this.name, saveLocalStorage: true })
            if (edata.isCancelled === true) return
            this.message({
                width: 350,
                height: 150,
                body: `<div class="w2ui-grid-save-search">
                            <span>${w2utils.lang('Save Report')}</span>
                            <input class="report-name w2ui-input" placeholder="${w2utils.lang('Report Name')}">
                       </div>`,
                buttons: `
                    <button id="grid-report-cancel" class="w2ui-btn">${w2utils.lang('Cancel')}</button>
                    <button id="grid-report-save" class="w2ui-btn w2ui-btn-blue" ${String(value).trim() == '' ? 'disabled': ''}>${w2utils.lang('Save')}</button>
                `
            }).open(async (event) => {
                query(event.detail.box).find('input, button').eq(0).val(value)
                await event.complete
                query(event.detail.box).find('#grid-report-cancel').on('click', () => {
                    this.message()
                })
                query(event.detail.box).find('#grid-report-save').on('click', () => {
                    let name = query(event.detail.box).find('.w2ui-message .report-name').val()
                    var filter = Object.assign({}, this.searchData);
                    var reportes = {};
                    if (name == null || name == '') {
                        w2alert(w2utils.lang('Report name not be empty.'))
                    }
                    else {
                        let t = []
                        Object.keys(filter).forEach(k => {
                            t.push(filter[k].id)
                        })
                        // Check if this.url is an object with a 'get' property, otherwise use this.url directly
                        var url = (typeof this.url === 'object' && this.url !== null && this.url.get) ? this.url.get : this.url;
                        
                        reportes = {
                            Name: name,
                            Url: url,
                            Grid: this.name,
                            Logic: this.last.logic,
                            Filters: JSON.stringify(t)
                        }
                        // Use fetch API (modern approach)
                        fetch('/Reports/Add', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams(reportes)
                        })
                        .then(response => response.json())
                        .then(res => {
                            if (typeof messager !== 'undefined') {
                                messager(res.success == true ? 'success' : 'warning', '', res.message)
                            } else if (typeof w2ui !== 'undefined' && w2ui.notify) {
                                w2ui.notify(res.success == true ? 'success' : 'warning', res.message)
                            }
                        })
                        .catch(error => {
                            if (typeof w2ui !== 'undefined' && w2ui.notify) {
                                w2ui.notify('error', w2utils.lang('An error occurred while saving the report.'))
                            }
                        })
                        this.message()
                        edata.finish({ name })
                    }
                })
                query(event.detail.box).find('input, button')
                    .off('.message')
                    .on('keydown.message', evt => {
                        let val = String(query(event.detail.box).find('.w2ui-message-body input').val()).trim()
                        if (evt.keyCode == 13 && val != '') {
                            query(event.detail.box).find('#grid-report-save').trigger('click') // enter
                        }
                        if (evt.keyCode == 27) { // escape
                            this.message()
                        }
                    })
                    .eq(0)
                    .on('input.message', evt => {
                        let $save = query(event.detail.box).closest('.w2ui-message').find('#grid-report-save')
                        if (String(query(event.detail.box).val()).trim() === '') {
                            $save.prop('disabled', true)
                        } else {
                            $save.prop('disabled', false)
                        }
                    })
                    .get(0)
                    .focus()
            })
        };

        /**
         * Grid verilerini Excel formatında export eder
         * @param {string} filename - Dosya adı (opsiyonel, .xlsx uzantısı otomatik eklenir)
         * @returns {void}
         */
        w2grid.prototype.exportToExcel = function() {
            if (typeof XLSX === 'undefined') {
                if (typeof messager !== 'undefined') {
                    messager('error', w2utils.lang('Error'), w2utils.lang('XLSX library is not loaded. Please load SheetJS library.'));
                } else if (typeof w2ui !== 'undefined' && w2ui.notify) {
                    w2ui.notify('error', w2utils.lang('XLSX library is not loaded. Please load SheetJS library.'));
                }
                return;
            }

            if (typeof FileSaver === 'undefined' && typeof saveAs === 'undefined') {
                if (typeof messager !== 'undefined') {
                    messager('error', w2utils.lang('Error'), w2utils.lang('FileSaver library is not loaded. Please load FileSaver.js file.'));
                } else if (typeof w2ui !== 'undefined' && w2ui.notify) {
                    w2ui.notify('error', w2utils.lang('FileSaver library is not loaded. Please load FileSaver.js file.'));
                }
                return;
            }
            //w2prompt
            w2prompt({
                label: w2utils.lang('Enter file name'),
                value: '',
                attrs: 'style="width: 200px"',
                title: w2utils.lang('Save'),
                ok_text: w2utils.lang('Ok'),
                ok_class: 'ok-class',
                cancel_text: w2utils.lang('Cancel'),
                cancel_class: 'cancel-class',
                width: 400,
                height: 200
            })
            .ok((event) => {
                if (event != null && event != "") {
                    var filename = event.detail.value;
                    var data = [];
                    var grid = this;
                    
                    // Grid kayıtlarını Excel formatına dönüştür
                    this.records.forEach(function(e) {
                        var rec = {};
                        grid.columns.forEach(function(key) {
                            if (key.hidden) return;
                            var x = e[key.field];
                            if (typeof x == "object" && x != null) {
                                x = x.text || x.name || JSON.stringify(x);
                            }
                            rec[key.text || key.field] = x;
                        });
                        data.push(rec);
                    });

                    if (data.length === 0) {
                        if (typeof messager !== 'undefined') {
                            messager('info', w2utils.lang('Info'), w2utils.lang('No records found to export.'));
                        } else if (typeof w2ui !== 'undefined' && w2ui.notify) {
                            w2ui.notify('info', w2utils.lang('No records found to export.'));
                        }
                        return;
                    }

                    // Excel workbook oluştur
                    var wb = XLSX.utils.book_new();
                    var ws = XLSX.utils.json_to_sheet(data);
                    XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
                    
                    var wopts = {
                        bookType: 'xlsx',
                        bookSST: false,
                        type: 'binary'
                    };
                    var wbout = XLSX.write(wb, wopts);

                    // Dosya adı belirleme
                    var saveFileName = filename || grid.name || 'export';
                    if (!saveFileName.endsWith('.xlsx')) {
                        saveFileName += '.xlsx';
                    }

                    // s2ab helper fonksiyonu
                    function s2ab(s) {
                        var buf = new ArrayBuffer(s.length);
                        var view = new Uint8Array(buf);
                        for (var i = 0; i != s.length; ++i) {
                            view[i] = s.charCodeAt(i) & 0xFF;
                        }
                        return buf;
                    }

                    // Dosyayı kaydet
                    var saveAsFunc = typeof saveAs !== 'undefined' ? saveAs : (typeof FileSaver !== 'undefined' && FileSaver.saveAs ? FileSaver.saveAs : null);
                    
                    if (saveAsFunc) {
                        saveAsFunc(new Blob([s2ab(wbout)], {
                            type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                        }), saveFileName);
                    } else {
                        if (typeof messager !== 'undefined') {
                            messager('error', w2utils.lang('Error'), w2utils.lang('saveAs function not found.'));
                        } else if (typeof w2ui !== 'undefined' && w2ui.notify) {
                            w2ui.notify('error', w2utils.lang('saveAs function not found.'));
                        }
                    }
                }
            })
            .cancel((event) => {
                if (typeof messager !== 'undefined') {
                    messager('info', w2utils.lang('Info'), w2utils.lang('Cancelled.'));
                } else if (typeof w2ui !== 'undefined' && w2ui.notify) {
                    w2ui.notify('info', w2utils.lang('Cancelled.'));
                }
            })
        };

        /**
         * Özelleştirilmiş getSearchesHTML fonksiyonu - Record limit dropdown'u içerir
         * @returns {string} HTML string
         */
        w2grid.prototype.getSearchesHTML = function() {
            console.log(this.last);
            this.last.limit = this.limit;
            let html = `
            <div class="search-title">
                ${w2utils.lang('Advanced Search')}
                <span style="float: right; padding-right: 10px;">
                    <select id="grid_${this.name}_record_limit" class="w2ui-input w2ui-limit">
                        <option value="-1" ${this.limit == 'All' || this.limit == -1 ? 'selected' : ''}>${w2utils.lang('All')}</option>
                        <option value="25" ${this.limit == '25' || this.limit == 25 ? 'selected' : ''}>25</option>
                        <option value="50" ${this.limit == '50' || this.limit == 50 ? 'selected' : ''}>50</option>
                        <option value="100" ${this.limit == '100' || this.limit == 100 ? 'selected' : ''}>100</option>
                        <option value="250" ${this.limit == '250' || this.limit == 250 ? 'selected' : ''}>250</option>
                        <option value="500" ${this.limit == '500' || this.limit == 500 ? 'selected' : ''}>500</option>
                        <option value="1000" ${this.limit == '1000' || this.limit == 1000 ? 'selected' : ''}>1000</option>
                    </select>
                </span>
                <span class="search-logic" style="float: right; padding-right: 10px; ${this.show.searchLogic ? '' : 'display: none'}">
                    <select id="grid_${this.name}_logic" class="w2ui-input">
                        <option value="AND" ${this.last.logic == 'AND' ? 'selected' : ''}>${w2utils.lang('All')}</option>
                        <option value="OR" ${this.last.logic == 'OR' ? 'selected' : ''}>${w2utils.lang('Any')}</option>
                    </select>
                </span>
            </div>
            <table cellspacing="0"><tbody>
        `
            for (let i = 0; i < this.searches.length; i++) {
                let s = this.searches[i]
                s.type = String(s.type).toLowerCase()
                if (s.hidden) continue
                // w2ui.js 2.0 uyumluluğu için attr ve text kullan
                if (s.attr == null) s.attr = ''
                if (s.text == null) s.text = ''
                // Eski versiyon uyumluluğu için inTag ve outTag desteği
                if (s.inTag != null && s.attr == '') s.attr = s.inTag
                if (s.outTag != null && s.text == '') s.text = s.outTag
                if (s.style == null) s.style = ''
                if (s.type == null) s.type = 'text'
                if (s.label == null && s.caption != null) {
                    console.log('NOTICE: grid search.caption property is deprecated, please use search.label. Search ->', s)
                    s.label = s.caption
                }
                let operator = `<select id="grid_${this.name}_operator_${i}" class="w2ui-input" data-change="initOperator|${i}">
                        ${this.getOperators(s.type, s.operators)}
                    </select>`
                html += `<tr>
                            <td class="caption">${(w2utils.lang(s.label) || '')}</td>
                            <td class="operator">${operator}</td>
                            <td class="value">`
                let tmpStyle
                switch (s.type) {
                    case 'text':
                    case 'alphanumeric':
                    case 'hex':
                    case 'color':
                    case 'list':
                    case 'combo':
                    case 'enum':
                        tmpStyle = 'width: 250px;'
                        if (['hex', 'color'].indexOf(s.type) != -1) tmpStyle = 'width: 90px;'
                        html += `<input rel="search" type="text" id="grid_${this.name}_field_${i}" name="${s.field}"
                                   class="w2ui-input" style="${tmpStyle + s.style}" ${s.attr}>`
                        break
                    case 'int':
                    case 'float':
                    case 'money':
                    case 'currency':
                    case 'percent':
                    case 'date':
                    case 'time':
                    case 'datetime':
                        tmpStyle = 'width: 90px;'
                        if (s.type == 'datetime') tmpStyle = 'width: 140px;'
                        html += `<input id="grid_${this.name}_field_${i}" name="${s.field}" ${s.attr} rel="search" type="text"
                                    class="w2ui-input" style="${tmpStyle + s.style}">
                                <span id="grid_${this.name}_range_${i}" style="display: none">&#160;-&#160;&#160;
                                    <input rel="search" type="text" class="w2ui-input" style="${tmpStyle + s.style}" id="grid_${this.name}_field2_${i}" name="${s.field}" ${s.attr}>
                                </span>`
                        break
                    case 'select':
                        html += `<select rel="search" class="w2ui-input" style="${s.style}" id="grid_${this.name}_field_${i}"
                                    name="${s.field}" ${s.attr}></select>`
                        break
                }
                html += s.text +
                        '    </td>' +
                        '</tr>'
            }
            html += `<tr>
                <td colspan="2" class="actions">
                    <button type="button" class="w2ui-btn close-btn" data-click="searchClose">${w2utils.lang('Close')}</button>
                </td>
                <td class="actions">
                    <button type="button" class="w2ui-btn" data-click="searchReset">${w2utils.lang('Reset')}</button>
                    <button type="button" class="w2ui-btn w2ui-btn-blue" data-click="search">${w2utils.lang('Search')}</button>
                </td>
            </tr></tbody></table>`
            return html
        };

        /**
         * searchOpen metodunu override ederek record limit dropdown event listener'ını ekler
         */
        let originalSearchOpen = w2grid.prototype.searchOpen;
        w2grid.prototype.searchOpen = function() {
            if (!this.box) return
            if (this.searches.length === 0) return
            // event before
            let edata = this.trigger('searchOpen', { target: this.name })
            if (edata.isCancelled === true) {
                return
            }
            let $btn = query(this.toolbar.box).find('.w2ui-grid-search-input .w2ui-search-drop')
            $btn.addClass('checked')
            // show search
            let tooltipPromise = w2tooltip.show({
                name: this.name + '-search-overlay',
                anchor: query(this.box).find('#grid_'+ this.name +'_search_all').get(0),
                position: 'bottom|top',
                html: this.getSearchesHTML(),
                align: 'left',
                arrowSize: 12,
                class: 'w2ui-grid-search-advanced',
                hideOn: ['doc-click']
            })
            
            if (tooltipPromise && typeof tooltipPromise.then === 'function') {
                tooltipPromise.then(event => {
                    this.initSearches()
                    this.last.search_opened = true
                    let overlay = query(`#w2overlay-${this.name}-search-overlay`)
                    overlay
                        .data('gridName', this.name)
                        .off('.grid-search')
                        .on('click.grid-search', () => {
                        // hide any tooltip opened by searches
                            overlay.find('input, select').each(el => {
                                let names = query(el).data('tooltipName')
                                if (names) names.forEach(name => {
                                    w2tooltip.hide(name)
                                })
                            })
                        })
                    w2utils.bindEvents(overlay.find('select, input, button'), this)
                    
                    // Record limit dropdown event listener
                    let recordLimitSelect = overlay.find(`#grid_${this.name}_record_limit`);
                    if (recordLimitSelect.length > 0) {
                        recordLimitSelect.off('change.record-limit').on('change.record-limit', (e) => {
                            let limit = e.target.value;
                            if (limit == '-1') {
                                this.limit = -1;
                            } else {
                                this.limit = limit;
                            }
                        });
                    }
                    
                    // init first field
                    let sfields = query(`#w2overlay-${this.name}-search-overlay *[rel=search]`)
                    if (sfields.length > 0) sfields[0].focus()
                    // event after
                    edata.finish()
                })
                .hide(event => {
                    $btn.removeClass('checked')
                    this.last.search_opened = false
                })
            }
            
            return tooltipPromise;
        };

        /**
         * .w2ui-limit class'ına sahip select elementler için genel event listener
         * Select value değiştiğinde grid'in limit'ini günceller ve refresh eder
         */
        if (typeof document !== 'undefined') {
            // Event delegation kullanarak dinamik olarak eklenen elementler için de çalışır
            query(document).off('change.w2ui-limit').on('change.w2ui-limit', '.w2ui-limit', function(e) {
                let selectElement = e.target;
                let selectId = selectElement.id;
                
                // id formatı: grid_${gridName}_record_limit
                // Grid adını çıkar
                if (selectId && selectId.startsWith('grid_') && selectId.endsWith('_record_limit')) {
                    let gridName = selectId.replace('grid_', '').replace('_record_limit', '');
                    
                    // w2ui[gridName] var mı kontrol et
                    if (typeof w2ui !== 'undefined' && w2ui[gridName]) {
                        let grid = w2ui[gridName];
                        let limitValue = selectElement.value;
                        
                        // Limit değerini güncelle
                        if (limitValue == '-1') {
                            grid.limit = -1;
                        } else {
                            let limitInt = parseInt(limitValue);
                            grid.limit = limitInt;
                            grid.limit = limitInt;
                        }
                    }
                }
            });
        }
        
        /**
         * Override requestComplete method
         * If action is 'load' and limit < 1, set hasMore to false and hide more buttons
         */
        let originalRequestComplete = w2grid.prototype.requestComplete;
        w2grid.prototype.requestComplete = function(data, action, callBack, resolve, reject) {
            // Call original method first
            originalRequestComplete.call(this, data, action, callBack, resolve, reject);
            console.log(data,action,callBack,resolve,reject);
            // If action is 'load' and limit < 1
            if (action === 'load' && this.limit < 1) {
                this.last.fetch.hasMore = false;
                query(this.box).find('#grid_'+ this.name +'_rec_more, #grid_'+ this.name +'_frec_more').hide();
            }
        };
    }

    // Global utility fonksiyonları

    /**
     * Türkçe karakterleri İngilizce karşılıklarına çevirir ve özel karakterleri temizler
     * @param {string} str - Temizlenecek string
     * @returns {string} Temizlenmiş string
     */
    function clearSpecialCharacter(str) {
        var charMap = {
            Ç: 'c',
            Ö: 'o',
            Ş: 's',
            İ: 'i',
            I: 'i',
            Ü: 'u',
            Ğ: 'g',
            ç: 'c',
            ö: 'o',
            ş: 's',
            ı: 'i',
            ü: 'u',
            ğ: 'g'
        };
        var str_array = str.split('');
        for (var i = 0, len = str_array.length; i < len; i++) {
            str_array[i] = charMap[str_array[i]] || str_array[i];
        }
        str = str_array.join('');
        return str.replace(" ", "_").replace("__", "_").replace(/[^a-z0-9-.çöşüğı]/gi, "").toLowerCase();
    }

    /**
     * Excel dosyasını JSON formatına dönüştürür
     * @param {File} file - Excel dosyası
     * @param {Function} callback - Callback fonksiyonu (status, headers, json) parametreleriyle çağrılır
     * @returns {void}
     */
        function ExcelToJSON(file, callback) {
            if (typeof XLSX === 'undefined') {
                callback({
                    status: false,
                    error: w2utils.lang('XLSX library is not loaded. Please load SheetJS library.')
                });
                return;
            }

        var reader = new FileReader();
        
        reader.onload = function(e) {
            try {
                var data = e.target.result;
                var workbook = XLSX.read(data, {
                    type: 'binary'
                });
                var XL_row_object = XLSX.utils.sheet_to_row_object_array(workbook.Sheets[workbook.SheetNames[0]]);
                
                if (XL_row_object.length > 10001) {
                    if (typeof messager !== 'undefined') {
                        messager('info', w2utils.lang('Warning'), w2utils.lang('Can contain a maximum of ${count} records.', { count: 10000 }));
                    } else if (typeof w2ui !== 'undefined' && w2ui.notify) {
                        w2ui.notify('info', w2utils.lang('Can contain a maximum of ${count} records.', { count: 10000 }));
                    }
                    callback({
                        status: false,
                        error: w2utils.lang('Record count limit exceeded')
                    });
                    return;
                }

                var keys = Object.keys(XL_row_object[0] || {});
                var jsonStringData = JSON.stringify(XL_row_object);
                var headersData = [{
                    field: "recid",
                    text: w2utils.lang('Id'),
                    size: '60px'
                }, {
                    field: "excelDesc",
                    text: w2utils.lang('Response'),
                    size: '100px'
                }];

                keys.forEach(function(element, index) {
                    var cleanKey = clearSpecialCharacter(element);
                    jsonStringData = jsonStringData.replaceAll('"' + element + '"', '"' + cleanKey + '"');
                    keys[index] = cleanKey;
                    headersData.push({
                        field: cleanKey,
                        text: cleanKey,
                        size: '120px'
                    });
                });

                var jsonData = JSON.parse(jsonStringData);
                
                callback({
                    status: true,
                    headers: headersData,
                    json: jsonData
                });
            } catch (ex) {
                callback({
                    status: false,
                    error: ex.message || ex
                });
            }
        };

        reader.onerror = function(ex) {
            callback({
                status: false,
                error: ex.message || w2utils.lang('File reading error')
            });
        };

        setTimeout(function() {
            reader.readAsBinaryString(file);
        }, 300);
    }

    // Global scope'a fonksiyonları ekle
    if (typeof window !== 'undefined') {
        window.clearSpecialCharacter = clearSpecialCharacter;
        window.ExcelToJSON = ExcelToJSON;
    }

    // Extension'ların yüklendiğini belirt
    if (typeof console !== 'undefined' && console.log) {
        console.log('w2ui.extensions.js loaded successfully');
    }

})();

// w2dialogsecond ve w2dialogthird sınıfları
// Eski versiyondan (w2ui..old.js) taşınan popup sınıfları
// Global scope'da w2popupsecond ve w2popupthird tanımla (w2ui.js'deki w2popup gibi)
let w2popupsecond, w2popupthird

if (typeof w2base !== 'undefined') {
    class DialogSecond extends w2base {
        constructor() {
            super()
            this.defaults   = {
                title: '',
                text: '',           // just a text (will be centered)
                body: '',
                buttons: '',
                width: 450,
                height: 250,
                focus: null,        // brings focus to the element, can be a number or selector
                actions: null,      // actions object
                style: '',          // style of the message div
                speed: 0.3,
                modal: false,
                maximized: false,   // this is a flag to show the state - to open the popup maximized use openMaximized instead
                keyboard: true,     // will close popup on esc if not modal
                showClose: true,
                showMax: false,
                transition: null,
                openMaximized: false,
                moved: false
            }
            this.name       = 'popup'
            this.status     = 'closed' // string that describes current status
            this.onOpen     = null
            this.onClose    = null
            this.onMax      = null
            this.onMin      = null
            this.onToggle   = null
            this.onKeydown  = null
            this.onAction   = null
            this.onMove     = null
            this.tmp        = {}
            // event handler for resize
            this.handleResize = (event) => {
                // if it was moved by the user, do not auto resize
                if (!this.options.moved) {
                    this.center(undefined, undefined, true)
                }
            }
        }
        /**
         * Sample calls
         * - w2popupsecond.open('ddd').ok(() => { w2popupsecond.close() })
         * - w2popupsecond.open('ddd', { height: 120 }).ok(() => { w2popupsecond.close() })
         * - w2popupsecond.open({ body: 'text', title: 'caption', actions: ["Close"] }).close(() => { w2popupsecond.close() })
         * - w2popupsecond.open({ body: 'text', title: 'caption', actions: { Close() { w2popupsecond.close() }} })
         */
        open(options) {
            let self = this
            if (this.status == 'closing' || query('#w2ui-popup-second').hasClass('animating')) {
                // if called when previous is closing
                this.close(true)
            }
            // get old options and merge them
            let old_options = this.options
            if (['string', 'number'].includes(typeof options)) {
                options = w2utils.extend({
                    title: 'Notification',
                    body: `<div class="w2ui-centered">${options}</div>`,
                    actions: { Ok() { self.close() }},
                    cancelAction: 'ok'
                }, arguments[1] ?? {})
            }
            if (options.text != null) options.body = `<div class="w2ui-centered w2ui-msg-text">${options.text}</div>`
            options = Object.assign({}, this.defaults, old_options, { title: '', body : '' }, options, { maximized: false })
            this.options = options
            // if new - reset event handlers
            if (query('#w2ui-popup-second').length === 0) {
                this.off('*')
                Object.keys(this).forEach(key => {
                    if (key.startsWith('on') && key != 'on') this[key] = null
                })
            }
            // reassign events
            Object.keys(options).forEach(key => {
                if (key.startsWith('on') && key != 'on' && options[key]) {
                    this[key] = options[key]
                }
            })
            options.width  = parseInt(options.width)
            options.height = parseInt(options.height)
            let edata, msg, tmp
            let { top, left } = this.center()
            let prom = {
                self: this,
                action(callBack) {
                    self.on('action.prom', callBack)
                    return prom
                },
                close(callBack) {
                    self.on('close.prom', callBack)
                    return prom
                },
                then(callBack) {
                    self.on('open:after.prom', callBack)
                    return prom
                }
            }
            // convert action arrays into buttons
            if (options.actions != null && !options.buttons) {
                options.buttons = ''
                Object.keys(options.actions).forEach((action) => {
                    let handler = options.actions[action]
                    let btnAction = action
                    if (typeof handler == 'function') {
                        options.buttons += `<button class="w2ui-btn w2ui-eaction" data-click='["action","${action}","event"]'>${action}</button>`
                    }
                    if (typeof handler == 'object') {
                        options.buttons += `<button class="w2ui-btn w2ui-eaction ${handler.class || ''}" name="${action}" data-click='["action","${action}","event"]'
                            style="${handler.style}" ${handler.attrs}>${handler.text || action}</button>`
                        btnAction = Array.isArray(options.actions) ? handler.text : action
                    }
                    if (typeof handler == 'string') {
                        options.buttons += `<button class="w2ui-btn w2ui-eaction" data-click='["action","${handler}","event"]'>${handler}</button>`
                        btnAction = handler
                    }
                    if (typeof btnAction == 'string') {
                        btnAction = btnAction[0].toLowerCase() + btnAction.substr(1).replace(/\s+/g, '')
                    }
                    prom[btnAction] = function (callBack) {
                        self.on('action.buttons', (event) => {
                            let target = event.detail.action[0].toLowerCase() + event.detail.action.substr(1).replace(/\s+/g, '')
                            if (target == btnAction) callBack(event)
                        })
                        return prom
                    }
                })
            }
            // check if message is already displayed
            if (query('#w2ui-popup-second').length === 0) {
                // trigger event
                edata = this.trigger('open', { target: 'popup', present: false })
                if (edata.isCancelled === true) return
                this.status = 'opening'
                // output message
                w2utils.lock(document.body, {
                    opacity: 0.3,
                    onClick: options.modal ? null : () => { this.close() }
                })
                let btn = ''
                if (options.showClose) {
                    btn += `<div class="w2ui-popup-second-button w2ui-popup-second-close">
                                <span class="w2ui-icon w2ui-icon-cross w2ui-eaction" data-mousedown="stop" data-click="close"></span>
                            </div>`
                }
                if (options.showMax) {
                    btn += `<div class="w2ui-popup-second-button w2ui-popup-second-max">
                                <span class="w2ui-icon w2ui-icon-box w2ui-eaction" data-mousedown="stop" data-click="toggle"></span>
                            </div>`
                }
                // first insert just body
                let styles = `
                    left: ${left}px;
                    top: ${top}px;
                    width: ${parseInt(options.width)}px;
                    height: ${parseInt(options.height)}px;
                    transition: ${options.speed}s
                `
                msg = `<div id="w2ui-popup-second" class="w2ui-popup-second w2ui-anim-open animating" style="${w2utils.stripSpaces(styles)}"></div>`
                query('body').append(msg)
                query('#w2ui-popup-second')[0]._w2popup = {
                    self: this,
                    created: new Promise((resolve) => { this._promCreated = resolve }),
                    opened: new Promise((resolve) => { this._promOpened = resolve }),
                    closing: new Promise((resolve) => { this._promClosing = resolve }),
                    closed: new Promise((resolve) => { this._promClosed = resolve }),
                }
                // then content
                styles = `${!options.title ? 'top: 0px !important;' : ''} ${!options.buttons ? 'bottom: 0px !important;' : ''}`
                msg = `
                    <span name="hidden-first" tabindex="0" style="position: absolute; top: -100px"></span>
                    <div class="w2ui-popup-second-title-btns">${btn}</div>
                    <div class="w2ui-popup-second-title" style="${!options.title ? 'display: none' : ''}"></div>
                    <div class="w2ui-box" style="${styles}">
                        <div class="w2ui-popup-second-body ${!options.title || ' w2ui-popup-second-no-title'}
                            ${!options.buttons || ' w2ui-popup-second-no-buttons'}" style="${options.style}">
                        </div>
                    </div>
                    <div class="w2ui-popup-second-buttons" style="${!options.buttons ? 'display: none' : ''}"></div>
                    <span name="hidden-last" tabindex="0" style="position: absolute; top: -100px"></span>
                `
                query('#w2ui-popup-second').html(msg)
                if (options.title) query('#w2ui-popup-second .w2ui-popup-second-title').append(w2utils.lang(options.title))
                if (options.buttons) query('#w2ui-popup-second .w2ui-popup-second-buttons').append(options.buttons)
                if (options.body) query('#w2ui-popup-second .w2ui-popup-second-body').append(options.body)
                // allow element to render
                setTimeout(() => {
                    query('#w2ui-popup-second')
                        .css('transition', options.speed + 's')
                        .removeClass('w2ui-anim-open')
                    w2utils.bindEvents('#w2ui-popup-second .w2ui-eaction', this)
                    query('#w2ui-popup-second').find('.w2ui-popup-second-body').show()
                    this._promCreated()
                }, 1)
                // clean transform
                clearTimeout(this._timer)
                this._timer = setTimeout(() => {
                    this.status = 'open'
                    self.setFocus(options.focus)
                    // event after
                    edata.finish()
                    this._promOpened()
                    query('#w2ui-popup-second').removeClass('animating')
                }, options.speed * 1000)
            } else {
                // trigger event
                edata = this.trigger('open', { target: 'popup', present: true })
                if (edata.isCancelled === true) return
                // check if size changed
                this.status = 'opening'
                if (old_options != null) {
                    if (!old_options.maximized && (old_options.width != options.width || old_options.height != options.height)) {
                        this.resize(options.width, options.height)
                    }
                    options.prevSize  = options.width + 'px:' + options.height + 'px'
                    options.maximized = old_options.maximized
                }
                // show new items
                let cloned = query('#w2ui-popup-second .w2ui-box').get(0).cloneNode(true)
                query(cloned).removeClass('w2ui-box').addClass('w2ui-box-temp').find('.w2ui-popup-second-body').empty().append(options.body)
                query('#w2ui-popup-second .w2ui-box').after(cloned)
                if (options.buttons) {
                    query('#w2ui-popup-second .w2ui-popup-second-buttons').show().html('').append(options.buttons)
                    query('#w2ui-popup-second .w2ui-popup-second-body').removeClass('w2ui-popup-second-no-buttons')
                    query('#w2ui-popup-second .w2ui-box, #w2ui-popup-second .w2ui-box-temp').css('bottom', '')
                } else {
                    query('#w2ui-popup-second .w2ui-popup-second-buttons').hide().html('')
                    query('#w2ui-popup-second .w2ui-popup-second-body').addClass('w2ui-popup-second-no-buttons')
                    query('#w2ui-popup-second .w2ui-box, #w2ui-popup-second .w2ui-box-temp').css('bottom', '0px')
                }
                if (options.title) {
                    query('#w2ui-popup-second .w2ui-popup-second-title')
                        .show()
                        .html((options.showClose
                            ? `<div class="w2ui-popup-second-button w2ui-popup-second-close">
                                    <span class="w2ui-icon w2ui-icon-cross w2ui-eaction" data-mousedown="stop" data-click="close"></span>
                                </div>`
                            : '') +
                            (options.showMax
                            ? `<div class="w2ui-popup-second-button w2ui-popup-second-max">
                                    <span class="w2ui-icon w2ui-icon-box w2ui-eaction" data-mousedown="stop" data-click="toggle"></span>
                                </div>`
                            : ''))
                        .append(options.title)
                    query('#w2ui-popup-second .w2ui-popup-second-body').removeClass('w2ui-popup-second-no-title')
                    query('#w2ui-popup-second .w2ui-box, #w2ui-popup-second .w2ui-box-temp').css('top', '')
                } else {
                    query('#w2ui-popup-second .w2ui-popup-second-title').hide().html('')
                    query('#w2ui-popup-second .w2ui-popup-second-body').addClass('w2ui-popup-second-no-title')
                    query('#w2ui-popup-second .w2ui-box, #w2ui-popup-second .w2ui-box-temp').css('top', '0px')
                }
                // transition
                let div_old = query('#w2ui-popup-second .w2ui-box')[0]
                let div_new = query('#w2ui-popup-second .w2ui-box-temp')[0]
                query('#w2ui-popup-second').addClass('animating')
                w2utils.transition(div_old, div_new, options.transition, () => {
                    // clean up
                    query(div_old).remove()
                    query(div_new).removeClass('w2ui-box-temp').addClass('w2ui-box')
                    let $body = query(div_new).find('.w2ui-popup-second-body')
                    if ($body.length == 1) {
                        $body[0].style.cssText = options.style
                        $body.show()
                    }
                    // focus on first button
                    self.setFocus(options.focus)
                    query('#w2ui-popup-second').removeClass('animating')
                })
                // call event onOpen
                this.status = 'open'
                edata.finish()
                w2utils.bindEvents('#w2ui-popup-second .w2ui-eaction', this)
                query('#w2ui-popup-second').find('.w2ui-popup-second-body').show()
            }
            if (options.openMaximized) {
                this.max()
            }
            // save new options
            options._last_focus = document.activeElement
            // keyboard events
            if (options.keyboard) {
                query(document.body).on('keydown', (event) => {
                    this.keydown(event)
                })
            }
            query(window).on('resize', this.handleResize)
            // initialize move
            tmp = {
                resizing : false,
                mvMove   : mvMove,
                mvStop   : mvStop
            }
            query('#w2ui-popup-second .w2ui-popup-second-title').on('mousedown', function(event) {
                if (!self.options.maximized) mvStart(event)
            })
            return prom
            // handlers
            function mvStart(evt) {
                if (!evt) evt = window.event
                self.status = 'moving'
                let rect = query('#w2ui-popup-second').get(0).getBoundingClientRect()
                Object.assign(tmp, {
                    resizing: true,
                    isLocked: query('#w2ui-popup-second > .w2ui-lock').length == 1 ? true : false,
                    x       : evt.screenX,
                    y       : evt.screenY,
                    pos_x   : rect.x,
                    pos_y   : rect.y,
                })
                if (!tmp.isLocked) self.lock({ opacity: 0 })
                query(document.body)
                    .on('mousemove.w2ui-popup-second', tmp.mvMove)
                    .on('mouseup.w2ui-popup-second', tmp.mvStop)
                if (evt.stopPropagation) evt.stopPropagation(); else evt.cancelBubble = true
                if (evt.preventDefault) evt.preventDefault(); else return false
            }
            function mvMove(evt) {
                if (tmp.resizing != true) return
                if (!evt) evt = window.event
                tmp.div_x = evt.screenX - tmp.x
                tmp.div_y = evt.screenY - tmp.y
                // trigger event
                let edata = self.trigger('move', { target: 'popup', div_x: tmp.div_x, div_y: tmp.div_y, originalEvent: evt })
                if (edata.isCancelled === true) return
                // default behavior
                query('#w2ui-popup-second').css({
                    'transition': 'none',
                    'transform' : 'translate3d('+ tmp.div_x +'px, '+ tmp.div_y +'px, 0px)'
                })
                self.options.moved = true
                // event after
                edata.finish()
            }
            function mvStop(evt) {
                if (tmp.resizing != true) return
                if (!evt) evt = window.event
                self.status = 'open'
                tmp.div_x      = (evt.screenX - tmp.x)
                tmp.div_y      = (evt.screenY - tmp.y)
                query('#w2ui-popup-second')
                    .css({
                        'left': (tmp.pos_x + tmp.div_x) + 'px',
                        'top' : (tmp.pos_y + tmp.div_y) + 'px'
                    })
                    .css({
                        'transition': 'none',
                        'transform' : 'translate3d(0px, 0px, 0px)'
                    })
                tmp.resizing = false
                query(document.body).off('.w2ui-popup-second')
                if (!tmp.isLocked) self.unlock()
            }
        }
        load(options) {
            return new Promise((resolve, reject) => {
                if (typeof options == 'string') {
                    options = { url: options }
                }
                if (options.url == null) {
                    console.log('ERROR: The url is not defined.')
                    reject('The url is not defined')
                    return
                }
                this.status = 'loading'
                let [url, selector] = String(options.url).split('#')
                if (url) {
                    fetch(url).then(res => res.text()).then(html => {
                        resolve(this.template(html, selector, options))
                    })
                }
            })
        }
        template(data, id, options = {}) {
            let html
            try {
                html = query(data)
            } catch (e) {
                html = query.html(data)
            }
            if (id) html = html.filter('#' + id)
            Object.assign(options, {
                width: parseInt(query(html).css('width')),
                height: parseInt(query(html).css('height')),
                title: query(html).find('[rel=title]').html(),
                body: query(html).find('[rel=body]').html(),
                buttons: query(html).find('[rel=buttons]').html(),
                style: query(html).find('[rel=body]').get(0).style.cssText,
            })
            return this.open(options)
        }
        action(action, event) {
            let click = this.options.actions[action]
            if (click instanceof Object && click.onClick) click = click.onClick
            // event before
            let edata = this.trigger('action', { action, target: 'popup', self: this,
                originalEvent: event, value: this.input ? this.input.value : null })
            if (edata.isCancelled === true) return
            // default actions
            if (typeof click === 'function') click.call(this, event)
            // event after
            edata.finish()
        }
        keydown(event) {
            if (this.options && !this.options.keyboard) return
            // trigger event
            let edata = this.trigger('keydown', { target: 'popup', originalEvent: event })
            if (edata.isCancelled === true) return
            // default behavior
            switch (event.keyCode) {
                case 27:
                    event.preventDefault()
                    if (query('#w2ui-popup-second .w2ui-message').length == 0) {
                        if (this.options.cancelAction) {
                            this.action(this.options.cancelAction)
                        } else {
                            this.close()
                        }
                    }
                    break
            }
            // event after
            edata.finish()
        }
        close(immediate) {
            // trigger event
            let edata = this.trigger('close', { target: 'popup' })
            if (edata.isCancelled === true) return
            let cleanUp = () => {
                // return template
                query('#w2ui-popup-second').remove()
                // restore active
                if (this.options._last_focus && this.options._last_focus.length > 0) this.options._last_focus.focus()
                this.status = 'closed'
                this.options = {}
                // event after
                edata.finish()
                this._promClosed()
            }
            if (query('#w2ui-popup-second').length === 0 || this.status == 'closed') { // already closed
                return
            }
            if (this.status == 'opening') { // if it is opening
                immediate = true
            }
            if (this.status == 'closing' && immediate === true) {
                cleanUp()
                clearTimeout(this.tmp.closingTimer)
                w2utils.unlock(document.body, 0)
                return
            }
            // default behavior
            this.status = 'closing'
            query('#w2ui-popup-second')
                .css('transition', this.options.speed + 's')
                .addClass('w2ui-anim-close animating')
            w2utils.unlock(document.body, 300)
            this._promClosing()
            if (immediate) {
                cleanUp()
            } else {
                this.tmp.closingTimer = setTimeout(cleanUp, this.options.speed * 1000)
            }
            // remove keyboard events
            if (this.options.keyboard) {
                query(document.body).off('keydown', this.keydown)
            }
            query(window).off('resize', this.handleResize)
        }
        toggle() {
            let edata = this.trigger('toggle', { target: 'popup' })
            if (edata.isCancelled === true) return
            // default action
            if (this.options.maximized === true) this.min(); else this.max()
            // event after
            setTimeout(() => {
                edata.finish()
            }, (this.options.speed * 1000) + 50)
        }
        max() {
            if (this.options.maximized === true) return
            // trigger event
            let edata = this.trigger('max', { target: 'popup' })
            if (edata.isCancelled === true) return
            // default behavior
            this.status = 'resizing'
            let rect = query('#w2ui-popup-second').get(0).getBoundingClientRect()
            this.options.prevSize = rect.width + ':' + rect.height
            // do resize
            this.resize(10000, 10000, () => {
                this.status    = 'open'
                this.options.maximized = true
                edata.finish()
            })
        }
        min() {
            if (this.options.maximized !== true) return
            let size = this.options.prevSize.split(':')
            // trigger event
            let edata = this.trigger('min', { target: 'popup' })
            if (edata.isCancelled === true) return
            // default behavior
            this.status = 'resizing'
            // do resize
            this.options.maximized = false
            this.resize(parseInt(size[0]), parseInt(size[1]), () => {
                this.status = 'open'
                this.options.prevSize  = null
                edata.finish()
            })
        }
        clear() {
            query('#w2ui-popup-second .w2ui-popup-second-title').html('')
            query('#w2ui-popup-second .w2ui-popup-second-body').html('')
            query('#w2ui-popup-second .w2ui-popup-second-buttons').html('')
        }
        reset() {
            this.open(this.defaults)
        }
        message(options) {
            return w2utils.message({
                owner: this,
                box  : query('#w2ui-popup-second').get(0),
                after: '.w2ui-popup-second-title'
            }, options)
        }
        confirm(options) {
            return w2utils.confirm({
                owner: this,
                box  : query('#w2ui-popup-second'),
                after: '.w2ui-popup-second-title'
            }, options)
        }
        setFocus(focus) {
            let box = query('#w2ui-popup-second')
            let sel = 'input, button, select, textarea, [contentEditable], .w2ui-input'
            if (focus != null) {
                let el = isNaN(focus)
                    ? box.find(sel).filter(focus).get(0)
                    : box.find(sel).get(focus)
                el?.focus()
            } else {
                let el = box.find('[name=hidden-first]').get(0)
                if (el) el.focus()
            }
            // keep focus/blur inside popup
            query(box).find(sel + ',[name=hidden-first],[name=hidden-last]')
                .off('.keep-focus')
                .on('blur.keep-focus', function (event) {
                    setTimeout(() => {
                        let focus = document.activeElement
                        let inside = query(box).find(sel).filter(focus).length > 0
                        let name = query(focus).attr('name')
                        if (!inside && focus && focus !== document.body) {
                            query(box).find(sel).get(0)?.focus()
                        }
                        if (name == 'hidden-last') {
                            query(box).find(sel).get(0)?.focus()
                        }
                        if (name == 'hidden-first') {
                            query(box).find(sel).get(-1)?.focus()
                        }
                    }, 1)
                })
        }
        lock(msg, showSpinner) {
            let args = Array.from(arguments)
            args.unshift(query('#w2ui-popup-second'))
            w2utils.lock(...args)
        }
        unlock(speed) {
            w2utils.unlock(query('#w2ui-popup-second'), speed)
        }
        center(width, height, force) {
            let maxW, maxH
            if (window.innerHeight == undefined) {
                maxW = parseInt(document.documentElement.offsetWidth)
                maxH = parseInt(document.documentElement.offsetHeight)
            } else {
                maxW = parseInt(window.innerWidth)
                maxH = parseInt(window.innerHeight)
            }
            width = parseInt(width ?? this.options.width)
            height = parseInt(height ?? this.options.height)
            if (this.options.maximized === true) {
                width = maxW
                height = maxH
            }
            if (maxW - 10 < width) width = maxW - 10
            if (maxH - 10 < height) height = maxH - 10
            let top  = (maxH - height) / 2
            let left = (maxW - width) / 2
            if (force) {
                query('#w2ui-popup-second').css({
                    'transition': 'none',
                    'top'   : top + 'px',
                    'left'  : left + 'px',
                    'width' : width + 'px',
                    'height': height + 'px'
                })
                this.resizeMessages() // then messages resize nicely
            }
            return { top, left, width, height }
        }
        resize(newWidth, newHeight, callBack) {
            let self = this
            if (this.options.speed == null) this.options.speed = 0
            // calculate new position
            let { top, left, width, height } = this.center(newWidth, newHeight)
            let speed = this.options.speed
            query('#w2ui-popup-second').css({
                'transition': `${speed}s width, ${speed}s height, ${speed}s left, ${speed}s top`,
                'top'   : top + 'px',
                'left'  : left + 'px',
                'width' : width + 'px',
                'height': height + 'px'
            })
            let tmp_int = setInterval(() => { self.resizeMessages() }, 10) // then messages resize nicely
            setTimeout(() => {
                clearInterval(tmp_int)
                self.resizeMessages()
                if (typeof callBack == 'function') callBack()
            }, (this.options.speed * 1000) + 50) // give extra 50 ms
        }
        // internal function
        resizeMessages() {
            // see if there are messages and resize them
            query('#w2ui-popup-second .w2ui-message').each(msg => {
                let mopt = msg._msg_options
                let popup = query('#w2ui-popup-second')
                if (parseInt(mopt.width) < 10) mopt.width = 10
                if (parseInt(mopt.height) < 10) mopt.height = 10
                let rect = popup[0].getBoundingClientRect()
                let titleHeight = parseInt(popup.find('.w2ui-popup-second-title')[0].clientHeight)
                let pWidth      = parseInt(rect.width)
                let pHeight     = parseInt(rect.height)
                // re-calc width
                mopt.width = mopt.originalWidth
                if (mopt.width > pWidth - 10) {
                    mopt.width = pWidth - 10
                }
                // re-calc height
                mopt.height = mopt.originalHeight
                if (mopt.height > pHeight - titleHeight - 5) {
                    mopt.height = pHeight - titleHeight - 5
                }
                if (mopt.originalHeight < 0) mopt.height = pHeight + mopt.originalHeight - titleHeight
                if (mopt.originalWidth < 0) mopt.width = pWidth + mopt.originalWidth * 2 // x 2 because there is left and right margin
                query(msg).css({
                    left    : ((pWidth - mopt.width) / 2) + 'px',
                    width   : mopt.width + 'px',
                    height  : mopt.height + 'px'
                })
            })
        }
    }
    w2popupsecond = new DialogSecond()

    class DialogThird extends w2base {
        constructor() {
            super()
            this.defaults   = {
                title: '',
                text: '',           // just a text (will be centered)
                body: '',
                buttons: '',
                width: 450,
                height: 250,
                focus: null,        // brings focus to the element, can be a number or selector
                actions: null,      // actions object
                style: '',          // style of the message div
                speed: 0.3,
                modal: false,
                maximized: false,   // this is a flag to show the state - to open the popup maximized use openMaximized instead
                keyboard: true,     // will close popup on esc if not modal
                showClose: true,
                showMax: false,
                transition: null,
                openMaximized: false,
                moved: false
            }
            this.name       = 'popup'
            this.status     = 'closed' // string that describes current status
            this.onOpen     = null
            this.onClose    = null
            this.onMax      = null
            this.onMin      = null
            this.onToggle   = null
            this.onKeydown  = null
            this.onAction   = null
            this.onMove     = null
            this.tmp        = {}
            // event handler for resize
            this.handleResize = (event) => {
                // if it was moved by the user, do not auto resize
                if (!this.options.moved) {
                    this.center(undefined, undefined, true)
                }
            }
        }
        /**
         * Sample calls
         * - w2popupthird.open('ddd').ok(() => { w2popupthird.close() })
         * - w2popupthird.open('ddd', { height: 120 }).ok(() => { w2popupthird.close() })
         * - w2popupthird.open({ body: 'text', title: 'caption', actions: ["Close"] }).close(() => { w2popupthird.close() })
         * - w2popupthird.open({ body: 'text', title: 'caption', actions: { Close() { w2popupthird.close() }} })
         */
        open(options) {
            let self = this
            if (this.status == 'closing' || query('#w2ui-popup-third').hasClass('animating')) {
                // if called when previous is closing
                this.close(true)
            }
            // get old options and merge them
            let old_options = this.options
            if (['string', 'number'].includes(typeof options)) {
                options = w2utils.extend({
                    title: 'Notification',
                    body: `<div class="w2ui-centered">${options}</div>`,
                    actions: { Ok() { self.close() }},
                    cancelAction: 'ok'
                }, arguments[1] ?? {})
            }
            if (options.text != null) options.body = `<div class="w2ui-centered w2ui-msg-text">${options.text}</div>`
            options = Object.assign({}, this.defaults, old_options, { title: '', body : '' }, options, { maximized: false })
            this.options = options
            // if new - reset event handlers
            if (query('#w2ui-popup-third').length === 0) {
                this.off('*')
                Object.keys(this).forEach(key => {
                    if (key.startsWith('on') && key != 'on') this[key] = null
                })
            }
            // reassign events
            Object.keys(options).forEach(key => {
                if (key.startsWith('on') && key != 'on' && options[key]) {
                    this[key] = options[key]
                }
            })
            options.width  = parseInt(options.width)
            options.height = parseInt(options.height)
            let edata, msg, tmp
            let { top, left } = this.center()
            let prom = {
                self: this,
                action(callBack) {
                    self.on('action.prom', callBack)
                    return prom
                },
                close(callBack) {
                    self.on('close.prom', callBack)
                    return prom
                },
                then(callBack) {
                    self.on('open:after.prom', callBack)
                    return prom
                }
            }
            // convert action arrays into buttons
            if (options.actions != null && !options.buttons) {
                options.buttons = ''
                Object.keys(options.actions).forEach((action) => {
                    let handler = options.actions[action]
                    let btnAction = action
                    if (typeof handler == 'function') {
                        options.buttons += `<button class="w2ui-btn w2ui-eaction" data-click='["action","${action}","event"]'>${action}</button>`
                    }
                    if (typeof handler == 'object') {
                        options.buttons += `<button class="w2ui-btn w2ui-eaction ${handler.class || ''}" name="${action}" data-click='["action","${action}","event"]'
                            style="${handler.style}" ${handler.attrs}>${handler.text || action}</button>`
                        btnAction = Array.isArray(options.actions) ? handler.text : action
                    }
                    if (typeof handler == 'string') {
                        options.buttons += `<button class="w2ui-btn w2ui-eaction" data-click='["action","${handler}","event"]'>${handler}</button>`
                        btnAction = handler
                    }
                    if (typeof btnAction == 'string') {
                        btnAction = btnAction[0].toLowerCase() + btnAction.substr(1).replace(/\s+/g, '')
                    }
                    prom[btnAction] = function (callBack) {
                        self.on('action.buttons', (event) => {
                            let target = event.detail.action[0].toLowerCase() + event.detail.action.substr(1).replace(/\s+/g, '')
                            if (target == btnAction) callBack(event)
                        })
                        return prom
                    }
                })
            }
            // check if message is already displayed
            if (query('#w2ui-popup-third').length === 0) {
                // trigger event
                edata = this.trigger('open', { target: 'popup', present: false })
                if (edata.isCancelled === true) return
                this.status = 'opening'
                // output message
                w2utils.lock(document.body, {
                    opacity: 0.3,
                    onClick: options.modal ? null : () => { this.close() }
                })
                let btn = ''
                if (options.showClose) {
                    btn += `<div class="w2ui-popup-third-button w2ui-popup-third-close">
                                <span class="w2ui-icon w2ui-icon-cross w2ui-eaction" data-mousedown="stop" data-click="close"></span>
                            </div>`
                }
                if (options.showMax) {
                    btn += `<div class="w2ui-popup-third-button w2ui-popup-third-max">
                                <span class="w2ui-icon w2ui-icon-box w2ui-eaction" data-mousedown="stop" data-click="toggle"></span>
                            </div>`
                }
                // first insert just body
                let styles = `
                    left: ${left}px;
                    top: ${top}px;
                    width: ${parseInt(options.width)}px;
                    height: ${parseInt(options.height)}px;
                    transition: ${options.speed}s
                `
                msg = `<div id="w2ui-popup-third" class="w2ui-popup-third w2ui-anim-open animating" style="${w2utils.stripSpaces(styles)}"></div>`
                query('body').append(msg)
                query('#w2ui-popup-third')[0]._w2popup = {
                    self: this,
                    created: new Promise((resolve) => { this._promCreated = resolve }),
                    opened: new Promise((resolve) => { this._promOpened = resolve }),
                    closing: new Promise((resolve) => { this._promClosing = resolve }),
                    closed: new Promise((resolve) => { this._promClosed = resolve }),
                }
                // then content
                styles = `${!options.title ? 'top: 0px !important;' : ''} ${!options.buttons ? 'bottom: 0px !important;' : ''}`
                msg = `
                    <span name="hidden-first" tabindex="0" style="position: absolute; top: -100px"></span>
                    <div class="w2ui-popup-third-title-btns">${btn}</div>
                    <div class="w2ui-popup-third-title" style="${!options.title ? 'display: none' : ''}"></div>
                    <div class="w2ui-box" style="${styles}">
                        <div class="w2ui-popup-third-body ${!options.title || ' w2ui-popup-third-no-title'}
                            ${!options.buttons || ' w2ui-popup-third-no-buttons'}" style="${options.style}">
                        </div>
                    </div>
                    <div class="w2ui-popup-third-buttons" style="${!options.buttons ? 'display: none' : ''}"></div>
                    <span name="hidden-last" tabindex="0" style="position: absolute; top: -100px"></span>
                `
                query('#w2ui-popup-third').html(msg)
                if (options.title) query('#w2ui-popup-third .w2ui-popup-third-title').append(w2utils.lang(options.title))
                if (options.buttons) query('#w2ui-popup-third .w2ui-popup-third-buttons').append(options.buttons)
                if (options.body) query('#w2ui-popup-third .w2ui-popup-third-body').append(options.body)
                // allow element to render
                setTimeout(() => {
                    query('#w2ui-popup-third')
                        .css('transition', options.speed + 's')
                        .removeClass('w2ui-anim-open')
                    w2utils.bindEvents('#w2ui-popup-third .w2ui-eaction', this)
                    query('#w2ui-popup-third').find('.w2ui-popup-third-body').show()
                    this._promCreated()
                }, 1)
                // clean transform
                clearTimeout(this._timer)
                this._timer = setTimeout(() => {
                    this.status = 'open'
                    self.setFocus(options.focus)
                    // event after
                    edata.finish()
                    this._promOpened()
                    query('#w2ui-popup-third').removeClass('animating')
                }, options.speed * 1000)
            } else {
                // trigger event
                edata = this.trigger('open', { target: 'popup', present: true })
                if (edata.isCancelled === true) return
                // check if size changed
                this.status = 'opening'
                if (old_options != null) {
                    if (!old_options.maximized && (old_options.width != options.width || old_options.height != options.height)) {
                        this.resize(options.width, options.height)
                    }
                    options.prevSize  = options.width + 'px:' + options.height + 'px'
                    options.maximized = old_options.maximized
                }
                // show new items
                let cloned = query('#w2ui-popup-third .w2ui-box').get(0).cloneNode(true)
                query(cloned).removeClass('w2ui-box').addClass('w2ui-box-temp').find('.w2ui-popup-third-body').empty().append(options.body)
                query('#w2ui-popup-third .w2ui-box').after(cloned)
                if (options.buttons) {
                    query('#w2ui-popup-third .w2ui-popup-third-buttons').show().html('').append(options.buttons)
                    query('#w2ui-popup-third .w2ui-popup-third-body').removeClass('w2ui-popup-third-no-buttons')
                    query('#w2ui-popup-third .w2ui-box, #w2ui-popup-third .w2ui-box-temp').css('bottom', '')
                } else {
                    query('#w2ui-popup-third .w2ui-popup-third-buttons').hide().html('')
                    query('#w2ui-popup-third .w2ui-popup-third-body').addClass('w2ui-popup-third-no-buttons')
                    query('#w2ui-popup-third .w2ui-box, #w2ui-popup-third .w2ui-box-temp').css('bottom', '0px')
                }
                if (options.title) {
                    query('#w2ui-popup-third .w2ui-popup-third-title')
                        .show()
                        .html((options.showClose
                            ? `<div class="w2ui-popup-third-button w2ui-popup-third-close">
                                    <span class="w2ui-icon w2ui-icon-cross w2ui-eaction" data-mousedown="stop" data-click="close"></span>
                                </div>`
                            : '') +
                            (options.showMax
                            ? `<div class="w2ui-popup-third-button w2ui-popup-third-max">
                                    <span class="w2ui-icon w2ui-icon-box w2ui-eaction" data-mousedown="stop" data-click="toggle"></span>
                                </div>`
                            : ''))
                        .append(options.title)
                    query('#w2ui-popup-third .w2ui-popup-third-body').removeClass('w2ui-popup-third-no-title')
                    query('#w2ui-popup-third .w2ui-box, #w2ui-popup-third .w2ui-box-temp').css('top', '')
                } else {
                    query('#w2ui-popup-third .w2ui-popup-third-title').hide().html('')
                    query('#w2ui-popup-third .w2ui-popup-third-body').addClass('w2ui-popup-third-no-title')
                    query('#w2ui-popup-third .w2ui-box, #w2ui-popup-third .w2ui-box-temp').css('top', '0px')
                }
                // transition
                let div_old = query('#w2ui-popup-third .w2ui-box')[0]
                let div_new = query('#w2ui-popup-third .w2ui-box-temp')[0]
                query('#w2ui-popup-third').addClass('animating')
                w2utils.transition(div_old, div_new, options.transition, () => {
                    // clean up
                    query(div_old).remove()
                    query(div_new).removeClass('w2ui-box-temp').addClass('w2ui-box')
                    let $body = query(div_new).find('.w2ui-popup-third-body')
                    if ($body.length == 1) {
                        $body[0].style.cssText = options.style
                        $body.show()
                    }
                    // focus on first button
                    self.setFocus(options.focus)
                    query('#w2ui-popup-third').removeClass('animating')
                })
                // call event onOpen
                this.status = 'open'
                edata.finish()
                w2utils.bindEvents('#w2ui-popup-third .w2ui-eaction', this)
                query('#w2ui-popup-third').find('.w2ui-popup-third-body').show()
            }
            if (options.openMaximized) {
                this.max()
            }
            // save new options
            options._last_focus = document.activeElement
            // keyboard events
            if (options.keyboard) {
                query(document.body).on('keydown', (event) => {
                    this.keydown(event)
                })
            }
            query(window).on('resize', this.handleResize)
            // initialize move
            tmp = {
                resizing : false,
                mvMove   : mvMove,
                mvStop   : mvStop
            }
            query('#w2ui-popup-third .w2ui-popup-third-title').on('mousedown', function(event) {
                if (!self.options.maximized) mvStart(event)
            })
            return prom
            // handlers
            function mvStart(evt) {
                if (!evt) evt = window.event
                self.status = 'moving'
                let rect = query('#w2ui-popup-third').get(0).getBoundingClientRect()
                Object.assign(tmp, {
                    resizing: true,
                    isLocked: query('#w2ui-popup-third > .w2ui-lock').length == 1 ? true : false,
                    x       : evt.screenX,
                    y       : evt.screenY,
                    pos_x   : rect.x,
                    pos_y   : rect.y,
                })
                if (!tmp.isLocked) self.lock({ opacity: 0 })
                query(document.body)
                    .on('mousemove.w2ui-popup-third', tmp.mvMove)
                    .on('mouseup.w2ui-popup-third', tmp.mvStop)
                if (evt.stopPropagation) evt.stopPropagation(); else evt.cancelBubble = true
                if (evt.preventDefault) evt.preventDefault(); else return false
            }
            function mvMove(evt) {
                if (tmp.resizing != true) return
                if (!evt) evt = window.event
                tmp.div_x = evt.screenX - tmp.x
                tmp.div_y = evt.screenY - tmp.y
                // trigger event
                let edata = self.trigger('move', { target: 'popup', div_x: tmp.div_x, div_y: tmp.div_y, originalEvent: evt })
                if (edata.isCancelled === true) return
                // default behavior
                query('#w2ui-popup-third').css({
                    'transition': 'none',
                    'transform' : 'translate3d('+ tmp.div_x +'px, '+ tmp.div_y +'px, 0px)'
                })
                self.options.moved = true
                // event after
                edata.finish()
            }
            function mvStop(evt) {
                if (tmp.resizing != true) return
                if (!evt) evt = window.event
                self.status = 'open'
                tmp.div_x      = (evt.screenX - tmp.x)
                tmp.div_y      = (evt.screenY - tmp.y)
                query('#w2ui-popup-third')
                    .css({
                        'left': (tmp.pos_x + tmp.div_x) + 'px',
                        'top' : (tmp.pos_y + tmp.div_y) + 'px'
                    })
                    .css({
                        'transition': 'none',
                        'transform' : 'translate3d(0px, 0px, 0px)'
                    })
                tmp.resizing = false
                query(document.body).off('.w2ui-popup-third')
                if (!tmp.isLocked) self.unlock()
            }
        }
        load(options) {
            return new Promise((resolve, reject) => {
                if (typeof options == 'string') {
                    options = { url: options }
                }
                if (options.url == null) {
                    console.log('ERROR: The url is not defined.')
                    reject('The url is not defined')
                    return
                }
                this.status = 'loading'
                let [url, selector] = String(options.url).split('#')
                if (url) {
                    fetch(url).then(res => res.text()).then(html => {
                        resolve(this.template(html, selector, options))
                    })
                }
            })
        }
        template(data, id, options = {}) {
            let html
            try {
                html = query(data)
            } catch (e) {
                html = query.html(data)
            }
            if (id) html = html.filter('#' + id)
            Object.assign(options, {
                width: parseInt(query(html).css('width')),
                height: parseInt(query(html).css('height')),
                title: query(html).find('[rel=title]').html(),
                body: query(html).find('[rel=body]').html(),
                buttons: query(html).find('[rel=buttons]').html(),
                style: query(html).find('[rel=body]').get(0).style.cssText,
            })
            return this.open(options)
        }
        action(action, event) {
            let click = this.options.actions[action]
            if (click instanceof Object && click.onClick) click = click.onClick
            // event before
            let edata = this.trigger('action', { action, target: 'popup', self: this,
                originalEvent: event, value: this.input ? this.input.value : null })
            if (edata.isCancelled === true) return
            // default actions
            if (typeof click === 'function') click.call(this, event)
            // event after
            edata.finish()
        }
        keydown(event) {
            if (this.options && !this.options.keyboard) return
            // trigger event
            let edata = this.trigger('keydown', { target: 'popup', originalEvent: event })
            if (edata.isCancelled === true) return
            // default behavior
            switch (event.keyCode) {
                case 27:
                    event.preventDefault()
                    if (query('#w2ui-popup-third .w2ui-message').length == 0) {
                        if (this.options.cancelAction) {
                            this.action(this.options.cancelAction)
                        } else {
                            this.close()
                        }
                    }
                    break
            }
            // event after
            edata.finish()
        }
        close(immediate) {
            // trigger event
            let edata = this.trigger('close', { target: 'popup' })
            if (edata.isCancelled === true) return
            let cleanUp = () => {
                // return template
                query('#w2ui-popup-third').remove()
                // restore active
                if (this.options._last_focus && this.options._last_focus.length > 0) this.options._last_focus.focus()
                this.status = 'closed'
                this.options = {}
                // event after
                edata.finish()
                this._promClosed()
            }
            if (query('#w2ui-popup-third').length === 0 || this.status == 'closed') { // already closed
                return
            }
            if (this.status == 'opening') { // if it is opening
                immediate = true
            }
            if (this.status == 'closing' && immediate === true) {
                cleanUp()
                clearTimeout(this.tmp.closingTimer)
                w2utils.unlock(document.body, 0)
                return
            }
            // default behavior
            this.status = 'closing'
            query('#w2ui-popup-third')
                .css('transition', this.options.speed + 's')
                .addClass('w2ui-anim-close animating')
            w2utils.unlock(document.body, 300)
            this._promClosing()
            if (immediate) {
                cleanUp()
            } else {
                this.tmp.closingTimer = setTimeout(cleanUp, this.options.speed * 1000)
            }
            // remove keyboard events
            if (this.options.keyboard) {
                query(document.body).off('keydown', this.keydown)
            }
            query(window).off('resize', this.handleResize)
        }
        toggle() {
            let edata = this.trigger('toggle', { target: 'popup' })
            if (edata.isCancelled === true) return
            // default action
            if (this.options.maximized === true) this.min(); else this.max()
            // event after
            setTimeout(() => {
                edata.finish()
            }, (this.options.speed * 1000) + 50)
        }
        max() {
            if (this.options.maximized === true) return
            // trigger event
            let edata = this.trigger('max', { target: 'popup' })
            if (edata.isCancelled === true) return
            // default behavior
            this.status = 'resizing'
            let rect = query('#w2ui-popup-third').get(0).getBoundingClientRect()
            this.options.prevSize = rect.width + ':' + rect.height
            // do resize
            this.resize(10000, 10000, () => {
                this.status    = 'open'
                this.options.maximized = true
                edata.finish()
            })
        }
        min() {
            if (this.options.maximized !== true) return
            let size = this.options.prevSize.split(':')
            // trigger event
            let edata = this.trigger('min', { target: 'popup' })
            if (edata.isCancelled === true) return
            // default behavior
            this.status = 'resizing'
            // do resize
            this.options.maximized = false
            this.resize(parseInt(size[0]), parseInt(size[1]), () => {
                this.status = 'open'
                this.options.prevSize  = null
                edata.finish()
            })
        }
        clear() {
            query('#w2ui-popup-third .w2ui-popup-third-title').html('')
            query('#w2ui-popup-third .w2ui-popup-third-body').html('')
            query('#w2ui-popup-third .w2ui-popup-third-buttons').html('')
        }
        reset() {
            this.open(this.defaults)
        }
        message(options) {
            return w2utils.message({
                owner: this,
                box  : query('#w2ui-popup-third').get(0),
                after: '.w2ui-popup-third-title'
            }, options)
        }
        confirm(options) {
            return w2utils.confirm({
                owner: this,
                box  : query('#w2ui-popup-third'),
                after: '.w2ui-popup-third-title'
            }, options)
        }
        setFocus(focus) {
            let box = query('#w2ui-popup-third')
            let sel = 'input, button, select, textarea, [contentEditable], .w2ui-input'
            if (focus != null) {
                let el = isNaN(focus)
                    ? box.find(sel).filter(focus).get(0)
                    : box.find(sel).get(focus)
                el?.focus()
            } else {
                let el = box.find('[name=hidden-first]').get(0)
                if (el) el.focus()
            }
            // keep focus/blur inside popup
            query(box).find(sel + ',[name=hidden-first],[name=hidden-last]')
                .off('.keep-focus')
                .on('blur.keep-focus', function (event) {
                    setTimeout(() => {
                        let focus = document.activeElement
                        let inside = query(box).find(sel).filter(focus).length > 0
                        let name = query(focus).attr('name')
                        if (!inside && focus && focus !== document.body) {
                            query(box).find(sel).get(0)?.focus()
                        }
                        if (name == 'hidden-last') {
                            query(box).find(sel).get(0)?.focus()
                        }
                        if (name == 'hidden-first') {
                            query(box).find(sel).get(-1)?.focus()
                        }
                    }, 1)
                })
        }
        lock(msg, showSpinner) {
            let args = Array.from(arguments)
            args.unshift(query('#w2ui-popup-third'))
            w2utils.lock(...args)
        }
        unlock(speed) {
            w2utils.unlock(query('#w2ui-popup-third'), speed)
        }
        center(width, height, force) {
            let maxW, maxH
            if (window.innerHeight == undefined) {
                maxW = parseInt(document.documentElement.offsetWidth)
                maxH = parseInt(document.documentElement.offsetHeight)
            } else {
                maxW = parseInt(window.innerWidth)
                maxH = parseInt(window.innerHeight)
            }
            width = parseInt(width ?? this.options.width)
            height = parseInt(height ?? this.options.height)
            if (this.options.maximized === true) {
                width = maxW
                height = maxH
            }
            if (maxW - 10 < width) width = maxW - 10
            if (maxH - 10 < height) height = maxH - 10
            let top  = (maxH - height) / 2
            let left = (maxW - width) / 2
            if (force) {
                query('#w2ui-popup-third').css({
                    'transition': 'none',
                    'top'   : top + 'px',
                    'left'  : left + 'px',
                    'width' : width + 'px',
                    'height': height + 'px'
                })
                this.resizeMessages() // then messages resize nicely
            }
            return { top, left, width, height }
        }
        resize(newWidth, newHeight, callBack) {
            let self = this
            if (this.options.speed == null) this.options.speed = 0
            // calculate new position
            let { top, left, width, height } = this.center(newWidth, newHeight)
            let speed = this.options.speed
            query('#w2ui-popup-third').css({
                'transition': `${speed}s width, ${speed}s height, ${speed}s left, ${speed}s top`,
                'top'   : top + 'px',
                'left'  : left + 'px',
                'width' : width + 'px',
                'height': height + 'px'
            })
            let tmp_int = setInterval(() => { self.resizeMessages() }, 10) // then messages resize nicely
            setTimeout(() => {
                clearInterval(tmp_int)
                self.resizeMessages()
                if (typeof callBack == 'function') callBack()
            }, (this.options.speed * 1000) + 50) // give extra 50 ms
        }
        // internal function
        resizeMessages() {
            // see if there are messages and resize them
            query('#w2ui-popup-third .w2ui-message').each(msg => {
                let mopt = msg._msg_options
                let popup = query('#w2ui-popup-third')
                if (parseInt(mopt.width) < 10) mopt.width = 10
                if (parseInt(mopt.height) < 10) mopt.height = 10
                let rect = popup[0].getBoundingClientRect()
                let titleHeight = parseInt(popup.find('.w2ui-popup-third-title')[0].clientHeight)
                let pWidth      = parseInt(rect.width)
                let pHeight     = parseInt(rect.height)
                // re-calc width
                mopt.width = mopt.originalWidth
                if (mopt.width > pWidth - 10) {
                    mopt.width = pWidth - 10
                }
                // re-calc height
                mopt.height = mopt.originalHeight
                if (mopt.height > pHeight - titleHeight - 5) {
                    mopt.height = pHeight - titleHeight - 5
                }
                if (mopt.originalHeight < 0) mopt.height = pHeight + mopt.originalHeight - titleHeight
                if (mopt.originalWidth < 0) mopt.width = pWidth + mopt.originalWidth * 2 // x 2 because there is left and right margin
                query(msg).css({
                    left    : ((pWidth - mopt.width) / 2) + 'px',
                    width   : mopt.width + 'px',
                    height  : mopt.height + 'px'
                })
            })
        }
    }
    w2popupthird = new DialogThird()
    
}

// UMD wrapper for w2popupsecond and w2popupthird (w2ui.js'deki gibi)
!(function(global, extensions) {
    if (typeof define == 'function' && define.amd) {
        return define(() => extensions)
    }
    if (typeof exports != 'undefined') {
        if (typeof module != 'undefined' && module.exports) {
            return exports = module.exports = extensions
        }
        global = exports
    }
    if (global) {
        Object.keys(extensions).forEach(key => {
            global[key] = extensions[key]
        })
    }
})(self, {
    w2popupsecond,
    w2popupthird
})

