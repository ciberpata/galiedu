<?php
// views/preguntas.php
if ($_SESSION['user_role'] == 6) {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}
$role = $_SESSION['user_role'];
$isSuperAdmin = ($role == 1);
$isAcademy = ($role == 2);
$isTeacher = ($role == 3 || $role == 4);
?>

<div id="toast"></div>

<div id="reassignModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <h3 class="mb-4"><?php echo __('key_modal_reassign_title'); ?></h3>
        <p class="text-muted mb-4"><?php echo __('key_modal_reassign_desc'); ?> <strong id="modalReassignCount">0</strong> <?php echo __('key_modal_reassign_desc_plural'); ?></p>
        <form id="frmReassign">
            <div class="mb-4">
                <label for="reassign_target_user" class="block text-muted mb-2"><?php echo __('key_modal_reassign_select'); ?></label>
                <select id="reassign_target_user" class="form-control" required>
                    <option value=""><?php echo __('key_modal_reassign_option_default'); ?></option>
                </select>
            </div>
            <div class="modal-footer-actions">
                <button type="button" class="btn-icon btn-modal-cancel" onclick="document.getElementById('reassignModal').classList.remove('active')"><?php echo __('key_btn_cancel'); ?></button>
                <button type="submit" class="btn-primary"><?php echo __('key_btn_reassign_confirm'); ?></button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="dashboard-header" style="margin-bottom: 1.5rem;">
        <h2 class="welcome-title"><i class="fa-solid fa-circle-question"></i> <?php echo __('key_questions_title'); ?></h2>

        <div class="header-actions">
            <button class="btn-icon" onclick="window.open('api/export_pdf.php?type=preguntas', '_blank')" title="<?php echo __('btn_export_pdf'); ?>">
                <i class="fa-solid fa-file-pdf text-danger"></i>
            </button>

            <button class="btn-primary" onclick="showSection('list')" title="<?php echo __('key_btn_list_title'); ?>">
                <i class="fa-solid fa-list"></i> <span class="mobile-hidden"><?php echo __('key_btn_list'); ?></span>
            </button>

            <button class="btn-icon" style="border:1px solid var(--border-color); border-radius:var(--radius); width:auto; padding:0.6rem 1.2rem;" onclick="prepareCreate()" title="<?php echo __('key_btn_new_import_title'); ?>">
                <i class="fa-solid fa-plus"></i> <span class="mobile-hidden"><?php echo __('key_btn_new_import'); ?></span>
            </button>
        </div>
    </div>

    <div id="sec-list">

        <?php if ($isAcademy || $isSuperAdmin || $isTeacher): ?>
            <div class="quick-filters-bar">
                <span class="quick-filter-label">
                    <i class="fa-solid fa-bolt"></i> <?php echo __('quick_filters_title'); ?>:
                </span>

                <?php if ($isAcademy || $isSuperAdmin): ?>
                    <button class="btn-quick-filter" onclick="applyQFilter('most_used', this)">
                        <i class="fa-solid fa-fire"></i> <?php echo __('qf_most_used_questions'); ?>
                    </button>
                <?php endif; ?>

                <button class="btn-quick-filter" onclick="applyQFilter('orphan', this)">
                    <i class="fa-solid fa-folder-open"></i> <?php echo __('qf_orphan_questions'); ?>
                </button>

                <?php if ($role == 3): ?>
                    <button class="btn-quick-filter" onclick="applyQFilter('shared', this)">
                        <i class="fa-solid fa-share-nodes"></i> <?php echo __('qf_shared_bank'); ?>
                    </button>
                <?php endif; ?>

                <button class="btn-quick-filter" onclick="applyQFilter('trash', this)" title="<?php echo __('trash'); ?>">
                    <i class="fa-solid fa-trash-can"></i> <?php echo __('trash'); ?>
                </button>

                <button class="btn-quick-filter" onclick="resetQFilters(this)" title="<?php echo __('qf_reset'); ?>">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        <?php endif; ?>

        <div class="search-panel">
            <div class="search-bar-wrapper">
                <input type="text" id="qSearch" class="form-control" placeholder="<?php echo __('key_search_placeholder'); ?>" onkeyup="debounceQLoad()">

                <?php if (!$isSuperAdmin && $role != 4): ?>
                    <select id="qFilterScope" class="form-control" style="max-width:200px;" onchange="loadQuestions()">
                        <option value="mine"><?php echo __('key_filter_my_questions'); ?></option>
                        <option value="shared_bank"><?php echo __('key_filter_shared_questions'); ?></option>
                    </select>
                <?php endif; ?>

                <button class="btn-icon" style="border:1px solid var(--border-color);" onclick="toggleAdvancedSearch(this)" title="<?php echo __('key_filter_advanced_title'); ?>">
                    <i class="fa-solid fa-filter"></i>
                </button>
            </div>

           <div class="advanced-search">
                <div>
                    <label class="block text-muted mb-2">Asignatura</label>
                    <input type="text" id="f_asignatura" class="form-control" placeholder="Buscar asignatura..." list="subjectsList" onkeyup="debounceQLoad()">
                    <datalist id="subjectsList"></datalist>
                </div>
                <div>
                    <label class="block text-muted mb-2">Nivel Educativo</label>
                    <input type="text" id="f_nivel" class="form-control" placeholder="Ej: 1º ESO..." onkeyup="debounceQLoad()">
                </div>
                <div>
                    <label class="block text-muted mb-2">Etiquetas</label>
                    <input type="text" id="f_taxonomia" class="form-control" placeholder="Buscar etiquetas..." onkeyup="debounceQLoad()">
                </div>
                <div>
                    <label class="block text-muted mb-2">Dificultad</label>
                    <select id="f_dificultad" class="form-control" onchange="loadQuestions()">
                        <option value="">Todas</option>
                        <option value="1">1 - Muy fácil</option>
                        <option value="2">2 - Fácil</option>
                        <option value="3">3 - Media</option>
                        <option value="4">4 - Difícil</option>
                        <option value="5">5 - Muy difícil</option>
                    </select>
                </div>
                <div>
                    <label class="block text-muted mb-2"><?php echo __('key_filter_lang'); ?></label>
                    <select id="f_idioma" class="form-control" onchange="loadQuestions()">
                        <option value=""><?php echo __('key_filter_all'); ?></option>
                        <option value="es">Castellano</option>
                        <option value="gl">Galego</option>
                        <option value="en">English</option>
                    </select>
                </div>
                <div>
                    <label class="block text-muted mb-2"><?php echo __('key_filter_section'); ?></label>
                    <input type="text" id="f_seccion" class="form-control" onkeyup="debounceQLoad()">
                </div>
                <?php if ($isSuperAdmin || $isAcademy): ?>
                    <div>
                        <label class="block text-muted mb-2">Propietario</label>
                        <select id="f_usuario" class="form-control" onchange="loadQuestions()">
                            <option value=""><?php echo __('key_filter_all'); ?></option>
                        </select>
                    </div>
                <?php endif; ?>
                <div>
                    <label class="block text-muted mb-2">Desde</label>
                    <input type="date" id="f_date_from" class="form-control" onchange="loadQuestions()">
                </div>
                <div>
                    <label class="block text-muted mb-2">Hasta</label>
                    <input type="date" id="f_date_to" class="form-control" onchange="loadQuestions()">
                </div>
                <div>
                    <label class="block mb-2">&nbsp;</label>
                    <button type="button" class="btn-quick-filter" onclick="clearAllFilters()" title="Limpiar todos los filtros">
                        <i class="fa-solid fa-eraser"></i> Limpiar Filtros
                    </button>
                </div>
            </div>
        </div>

        <div class="pagination-bar">
            <div style="display:flex; align-items:center; gap:10px;">
                <label class="text-muted"><?php echo __('key_pagination_show'); ?> </label>
                <select id="limitSelectTop" onchange="changeQLimit(this.value)" class="form-control" style="width:auto; padding:5px;">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div class="pagination-controls">
                <button class="btn-icon" style="border:1px solid var(--border-color)" onclick="prevQPage()" id="btnPrevTop"><i class="fa-solid fa-chevron-left"></i></button>
                <span id="pageInfoTop" class="text-muted" style="margin:0 10px;"></span>
                <button class="btn-icon" style="border:1px solid var(--border-color)" onclick="nextQPage()" id="btnNextTop"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </div>

        <div class="bulk-actions-panel" id="bulkActionsPanel">
            <span style="font-weight:bold;" id="selectionCount">0 <?php echo __('key_bulk_selected'); ?></span>
            <button class="btn-icon" onclick="bulkAction('delete')" title="Borrar selección" style="color:var(--danger-color);">
                <i class="fa-solid fa-trash"></i>
            </button>
            <button class="btn-icon" onclick="bulkAction('restore')" title="Restaurar selección" style="color:var(--success-color);">
                <i class="fa-solid fa-trash-arrow-up"></i>
            </button>
            <button class="btn-icon" onclick="openReassignModal()" title="<?php echo __('key_bulk_reassign_title'); ?>" style="margin-left: 1rem;">
                <i class="fa-solid fa-user-pen"></i> <?php echo __('key_bulk_reassign'); ?>
            </button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width:20px;"><input type="checkbox" id="selectAllCheck" class="header-checkbox" onclick="toggleSelectAll(this)"></th>
                        <th class="sortable" onclick="changeQSort('id_pregunta')" style="width:40px;">ID <i id="icon-id_pregunta" class="fa-solid fa-sort"></i></th>
                        <th class="sortable" onclick="changeQSort('texto')"><?php echo __('key_header_question'); ?> <i id="icon-texto" class="fa-solid fa-sort"></i></th>
                        <th class="sortable" onclick="changeQSort('idioma')" style="width:60px;">IDIOMA <i id="icon-idioma" class="fa-solid fa-sort"></i></th>
                        <th class="sortable" onclick="changeQSort('nivel_educativo')">Nivel <i id="icon-nivel_educativo" class="fa-solid fa-sort"></i></th>
                        <th class="sortable" onclick="changeQSort('seccion')">Sección <i id="icon-seccion" class="fa-solid fa-sort"></i></th>
                        <th class="sortable" onclick="changeQSort('id_asignatura')">Asignatura <i id="icon-id_asignatura" class="fa-solid fa-sort"></i></th>
                        <th class="sortable text-center" onclick="changeQSort('dificultad')" style="width:40px;">DIF <i id="icon-dificultad" class="fa-solid fa-sort"></i></th>
                        <th>Etiquetas</th> <?php if ($isSuperAdmin || $isAcademy): ?>
                            
                        <?php endif; ?>
                        <?php if ($isSuperAdmin || $isAcademy): ?>
                            <th class="sortable" onclick="changeQSort('nombre_propietario')">Propietario <i id="icon-nombre_propietario" class="fa-solid fa-sort"></i></th>
                        <?php endif; ?>
                        <th class="text-right"><?php echo __('key_header_actions'); ?></th>
                    </tr>
                </thead>
                <tbody id="questionsTableBody"></tbody>
            </table>
        </div>

        <div class="pagination-bar">
            <div id="totalRecordsInfo" class="text-muted"></div>
            <div class="pagination-controls">
                <button class="btn-icon" style="border:1px solid var(--border-color)" onclick="prevQPage()" id="btnPrevBottom"><i class="fa-solid fa-chevron-left"></i></button>
                <span id="pageInfoBottom" class="text-muted" style="margin:0 10px;"></span>
                <button class="btn-icon" style="border:1px solid var(--border-color)" onclick="nextQPage()" id="btnNextBottom"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </div>
    </div>

    <div id="sec-create" class="hidden">
        <div class="tabs-header" style="display:flex; gap:10px; margin-bottom:1rem; border-bottom:1px solid var(--border-color);">
            <button class="tab-btn active" id="btn-tab-manual" onclick="openSubTab('manual', this)" style="padding:10px; background:none; border:none; border-bottom:2px solid var(--primary); font-weight:bold; color:var(--primary); cursor:pointer;">
                <?php echo __('key_tab_manual'); ?>
            </button>
            <button class="tab-btn" id="btn-tab-import" onclick="openSubTab('import', this)" style="padding:10px; background:none; border:none; border-bottom:2px solid transparent; color:var(--text-muted); cursor:pointer;">
                <?php echo __('key_tab_import'); ?>
            </button>
        </div>

        <div id="tab-manual" class="tab-content active" style="margin-top:20px;">
            <form id="frmManual">
                <input type="hidden" name="action" id="formAction" value="create_manual">
                <input type="hidden" name="id_pregunta" id="editId" value="">

                <?php if ($isAcademy || $isSuperAdmin): ?>
                    <div class="mb-4 p-2" style="background:var(--bg-body); border-radius:var(--radius); border-left:4px solid var(--primary);">
                        <label class="block text-muted mb-1" style="font-size:0.8rem;"><?php echo __('key_form_assign_to'); ?></label>
                        <select name="target_user_id" id="manual_target_user" class="form-control form-control-sm">
                            <option value="<?php echo $_SESSION['user_id']; ?>"><?php echo $isSuperAdmin ? 'Mi mismo / Superadmin' : __('key_form_assign_to_self'); ?></option>
                        </select>
                    </div>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                    <div>
                        <div class="mb-3">
                            <label class="block text-muted mb-1"><strong><?php echo __('key_form_text'); ?> *</strong></label>
                            <textarea name="texto" id="inp_texto" class="form-control" required rows="4" style="resize:vertical;"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="block text-muted mb-1">Etiquetas (separadas por comas)</label>
                            <input type="text" name="etiquetas" id="inp_etiquetas" class="form-control" placeholder="Ej: algebra, examen1, primaria...">
                        </div>
                    </div>

                    <div style="background: var(--bg-body); padding: 15px; border-radius: var(--radius); border: 1px solid var(--border-color);">
                        <div class="mb-3">
                            <label class="block text-muted mb-1">Asignatura</label>
                            <input type="text" name="id_asignatura" id="inp_asignatura" class="form-control" list="subjectsList" placeholder="Escribe o selecciona...">
                            <datalist id="subjectsList"></datalist>
                        </div>
                        <div class="mb-3">
                            <label class="block text-muted mb-1">Nivel Educativo</label>
                            <input type="text" name="nivel_educativo" id="inp_nivel" class="form-control" placeholder="Ej: 1º ESO">
                        </div>
                        <div class="mb-3">
                            <label class="block text-muted mb-1">Dificultad</label>
                            <select name="dificultad" id="inp_dificultad" class="form-control">
                                <option value="1">1 - Muy fácil</option>
                                <option value="2">2 - Fácil</option>
                                <option value="3" selected>3 - Media</option>
                                <option value="4">4 - Difícil</option>
                                <option value="5">5 - Muy difícil</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 10px;">
                    <div>
                        <label class="block text-muted mb-1"><?php echo __('key_form_section'); ?></label>
                        <input type="text" name="seccion" id="inp_seccion" class="form-control" list="sectionsList" placeholder="Escribe o selecciona...">
                        <datalist id="sectionsList"></datalist>
                    </div>
                    <div>
                        <label class="block text-muted mb-1"><?php echo __('key_form_lang'); ?></label>
                        <select name="idioma" id="inp_idioma" class="form-control">
                            <option value="es">Castellano</option>
                            <option value="gl">Galego</option>
                            <option value="en">English</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-muted mb-1">Tipo / Tiempo</label>
                        <div style="display:flex; gap:5px;">
                            <select name="tipo" class="form-control" id="manual_tipo" onchange="renderManualOptions()">
                                <option value="quiz">Quiz</option>
                                <option value="verdadero_falso">V/F</option>
                            </select>
                            <input type="number" name="tiempo_limite" id="inp_tiempo" class="form-control" value="20" title="Segundos">
                        </div>
                    </div>
                    <div style="display:flex; align-items:flex-end;">
                        <label class="switch-label" style="margin-bottom:8px;">
                            <input type="checkbox" name="doble_valor" id="chk_doble" value="1">
                            <span><strong>2x Puntos</strong></span>
                        </label>
                    </div>
                </div>

                <div style="background:var(--bg-surface); border:1px solid var(--border-color); padding:15px; border-radius:var(--radius); margin-top:15px;">
                    <h4 style="margin:0 0 10px 0; font-size:0.9rem; color:var(--primary);"><?php echo __('key_form_answers'); ?></h4>
                    <div id="manual-options-container"></div>
                </div>

                <div class="modal-footer-actions">
                    <button type="button" class="btn-icon btn-modal-cancel" onclick="cancelEdit()"><?php echo __('key_btn_cancel'); ?></button>
                    <button type="submit" class="btn-primary" id="btnSaveManual"><?php echo __('key_btn_save'); ?></button>
                </div>
            </form>
        </div>

        <div id="tab-import" class="tab-content hidden" style="margin-top:20px;">
            <div class="step-wizard">
                <span class="step-item active" id="st-1"><?php echo __('key_import_step1'); ?></span>
                <span class="step-item" id="st-2"><?php echo __('key_import_step2'); ?></span>
                <span class="step-item" id="st-3"><?php echo __('key_import_step3'); ?></span>
            </div>

            <div id="import-step-1">
                <?php if ($isAcademy || $isSuperAdmin): ?>
                    <div class="mb-4" style="max-width:100%;">
                        <label class="block text-muted mb-2"><?php echo __('key_import_destination'); ?></label>
                        <select id="import_target_user" class="form-control">
                            <option value="<?php echo $_SESSION['user_id']; ?>"><?php echo __('key_import_my_library'); ?></option>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="import-area" id="dropZone" style="border: 2px dashed var(--border-color); padding: 40px; text-align: center; border-radius: var(--radius); background: var(--bg-body); cursor: pointer;">
                    <i class="fa-solid fa-cloud-arrow-up" style="font-size:3.5rem; color:var(--primary); margin-bottom:1.5rem;"></i>
                    <p class="text-muted mb-3" style="font-size: 1.1rem;"><strong>Arrastra archivos CSV o Excel aquí</strong></p>
                    <p class="text-muted mb-4" style="font-size: 0.9rem;">O si lo prefieres, selecciona el archivo manualmente:</p>
                    
                    <div style="max-width: 400px; margin: 0 auto;">
                        <input type="file" id="fileInput" accept=".csv, .xlsx, .xls, .ods" class="form-control" style="background: white;">
                    </div>
                </div>

                <div class="mt-4" style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="text-muted" style="font-size: 0.85rem;"><i class="fa-solid fa-info-circle"></i> Formatos soportados: .csv, .xlsx, .xls, .ods</span>
                    <a href="api/preguntas.php?action=download_template" target="_blank" class="btn-icon" style="border:1px solid var(--border-color); border-radius:4px; padding:0.5rem 1rem; text-decoration:none;">
                        <i class="fa-solid fa-download"></i> <?php echo __('key_import_download_template'); ?>
                    </a>
                </div>
            </div>

            <div id="import-mapping-step" class="hidden">
                <h4 style="color:var(--primary); margin-bottom:10px;"><?php echo __('key_import_mapping_title'); ?></h4>
                <p class="text-muted mb-3"><?php echo __('key_import_mapping_desc'); ?></p>
                <div style="max-height:400px; overflow-y:auto; border:1px solid var(--border-color); border-radius:var(--radius); padding:1rem;">
                    <table class="table-mini" style="width:100%;">
                        <thead>
                            <tr>
                                <th width="40%"><?php echo __('key_col_system'); ?></th>
                                <th><?php echo __('key_col_file'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="mappingTableBody"></tbody>
                    </table>
                </div>
                <div class="text-right mt-3 modal-footer-actions">
                    <button class="btn-icon btn-modal-cancel" onclick="resetImport()"><?php echo __('key_btn_cancel'); ?></button>
                    <button class="btn-primary" onclick="executeQImport()"><?php echo __('key_import_btn_import'); ?></button>
                </div>
            </div>

            <div id="import-step-2" class="hidden">
                <div id="validation-log" class="mb-3" style="background:#1e293b; color:#00ff9d; padding:1rem; border-radius:5px;"></div>
                <div class="text-right">
                    <button class="btn-icon btn-modal-cancel" onclick="resetImport()"><?php echo __('key_btn_cancel'); ?></button>
                    <button class="btn-primary" id="btn-confirm-import" onclick="executeQImport()" disabled><?php echo __('key_import_btn_import'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // --- Variables de Estado ---
    let qState = {
        page: 1,
        limit: 10,
        sort: 'texto',
        order: 'ASC',
        totalPages: 1
    };
    let searchTimeout;
    const isSuperAdmin = <?php echo $isSuperAdmin ? 'true' : 'false'; ?>;
    const isAcademy = <?php echo $isAcademy ? 'true' : 'false'; ?>;
    let selectedQuestions = new Set();
    let currentQFilter = '';
    let currentScope = 'mine';

    // Traducciones seguras
    const LANG_LOADING = <?php echo json_encode(__('key_js_loading')); ?>;
    const LANG_NO_RESULTS = <?php echo json_encode(__('key_js_no_results')); ?>;
    const LANG_TRUE = <?php echo json_encode(__('key_form_answer_true')); ?>;
    const LANG_FALSE = <?php echo json_encode(__('key_form_answer_false')); ?>;
    

// Esta es la función que te faltaba definir:
const LANG_CONFIRM_DELETE = <?php echo json_encode(__('game_js_confirm_delete')); ?>;
    let fileToImport = null;

    function renderManualOptions(existingOptions = null) {
        const container = document.getElementById('manual-options-container');
        if (!container) return;
        container.innerHTML = '';
        
        const letters = ['a', 'b', 'c', 'd'];
        letters.forEach((l, index) => {
            // El API guarda un array, por lo que accedemos por índice numérico
            const optData = (existingOptions && existingOptions[index]) ? existingOptions[index] : null;
            const val = optData ? optData.texto : '';
            const isCorr = optData ? optData.es_correcta : false;
            
            container.innerHTML += `
                <div class="option-row" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <input type="radio" name="correcta_manual" value="${l.toUpperCase()}" id="rad_${l}" ${isCorr ? 'checked' : ''}>
                    <label style="width: 25px; font-weight: bold; margin:0;">${l.toUpperCase()}:</label>
                    <input type="text" class="form-control" id="opt_${l}" placeholder="Opción ${l.toUpperCase()}..." value="${val}">
                </div>`;
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadQuestions();
        renderManualOptions();
        if (isAcademy || isSuperAdmin) loadUsersForSelect();
        
        const frmReassign = document.getElementById('frmReassign');
        if (frmReassign) frmReassign.addEventListener('submit', handleReassignSubmit);

        // Listeners para que la importación se lance al elegir archivo
        const fileInput = document.getElementById('fileInput');
        if (fileInput) {
            fileInput.onchange = (e) => {
                if (e.target.files.length) {
                    fileToImport = e.target.files[0];
                    validateQFile();
                }
            };
        }

        // Listener para Guardar Pregunta Manual
        const frmManual = document.getElementById('frmManual');
        if (frmManual) {
            frmManual.addEventListener('submit', async (e) => {
                e.preventDefault();
                const fd = new FormData(frmManual);
                const data = Object.fromEntries(fd.entries());
                
                // Construir el array de opciones
                data.opciones = [];
                const correct = fd.get('correcta_manual');
                ['a','b','c','d'].forEach(l => {
                    const texto = document.getElementById('opt_'+l).value;
                    if (texto.trim() !== '') {
                        data.opciones.push({
                            texto: texto,
                            es_correcta: (l.toUpperCase() === correct)
                        });
                    }
                });

                const res = await fetch('api/preguntas.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const json = await res.json();
                if (json.success) {
                    showToast("Pregunta guardada");
                    cancelEdit();
                    loadQuestions();
                } else {
                    showToast(json.error || "Error al guardar", "error");
                }
            });
        }

        const dropZone = document.getElementById('dropZone');
        if (dropZone) {
            dropZone.ondragover = (e) => { e.preventDefault(); dropZone.style.borderColor = 'var(--primary)'; };
            dropZone.ondragleave = () => { dropZone.style.borderColor = 'var(--border-color)'; };
            dropZone.ondrop = (e) => {
                e.preventDefault();
                if (e.dataTransfer.files.length) {
                    fileToImport = e.dataTransfer.files[0];
                    validateQFile();
                }
            };
        }
    });

    // UI Helpers
    function showToast(msg, type='success') {
        const x = document.getElementById("toast");
        if (!x) return;
        
        x.innerText = msg;
        x.style.backgroundColor = type === 'error' ? 'var(--danger-color)' : 'var(--primary)';
        
        // Añadimos la clase para mostrarlo
        x.classList.add("show");
        
        // Lo ocultamos exactamente tras 1 segundo (1000ms)
        setTimeout(() => { 
            x.classList.remove("show");
        }, 1000);
    }

    function showSection(sec) {
        document.getElementById('sec-list').classList.add('hidden');
        document.getElementById('sec-create').classList.add('hidden');
        document.getElementById('sec-' + sec).classList.remove('hidden');
    }

    function openSubTab(name, btn) {
        document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
        document.getElementById('tab-' + name).classList.remove('hidden');
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('active');
            b.style.color = 'var(--text-muted)';
            b.style.borderBottomColor = 'transparent';
        });
        btn.classList.add('active');
        btn.style.color = 'var(--primary)';
        btn.style.borderBottomColor = 'var(--primary)';
    }

    function toggleAdvancedSearch(button) {
        const searchPanel = button.closest('.search-panel');
        if (searchPanel) {
            const advancedSearch = searchPanel.querySelector('.advanced-search');
            advancedSearch.classList.toggle('open');
        }
    }

    // Listado
    function debounceQLoad() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            qState.page = 1;
            loadQuestions();
        }, 300);
    }

    function changeQSort(col) {
        if (qState.sort === col) qState.order = (qState.order === 'ASC') ? 'DESC' : 'ASC';
        else {
            qState.sort = col;
            qState.order = 'ASC';
        }
        document.querySelectorAll('#sec-list th i').forEach(i => {
            if (i.id.startsWith('icon-')) i.className = 'fa-solid fa-sort';
        });
        const icon = document.getElementById(`icon-${col}`);
        if (icon) icon.className = `fa-solid fa-sort-${qState.order === 'ASC' ? 'up' : 'down'}`;
        loadQuestions();
    }

    function changeQLimit(val) {
        qState.limit = parseInt(val);
        qState.page = 1;
        loadQuestions();
    }

    function prevQPage() {
        if (qState.page > 1) {
            qState.page--;
            loadQuestions();
        }
    }

    function nextQPage() {
        if (qState.page < qState.totalPages) {
            qState.page++;
            loadQuestions();
        }
    }

    async function loadQuestions() {
        const search = document.getElementById('qSearch').value;
        const scopeElem = document.getElementById('qFilterScope');
        if (scopeElem && currentQFilter !== 'orphan') currentScope = scopeElem.value;
        else if (!scopeElem) currentScope = 'mine';

        const params = new URLSearchParams({
            action: 'list',
            search,
            scope: currentScope,
            f_idioma: document.getElementById('f_idioma').value,
            f_asignatura: document.getElementById('f_asignatura').value,
            f_dificultad: document.getElementById('f_dificultad').value,
            f_taxonomia: document.getElementById('f_taxonomia').value,
            f_nivel: document.getElementById('f_nivel').value,
            f_seccion: document.getElementById('f_seccion').value,
            f_usuario: document.getElementById('f_usuario') ? document.getElementById('f_usuario').value : '',
            date_from: document.getElementById('f_date_from').value,
            date_to: document.getElementById('f_date_to').value,
            sort: qState.sort,
            order: qState.order,
            page: qState.page,
            limit: qState.limit,
            special: currentQFilter
        });

        const colspan = (isSuperAdmin || isAcademy) ? 11 : 10;
        const tbody = document.getElementById('questionsTableBody');
        tbody.innerHTML = `<tr><td colspan="${colspan}" class="text-center">${LANG_LOADING}</td></tr>`;

        if (document.getElementById('selectAllCheck')) document.getElementById('selectAllCheck').checked = false;
        selectedQuestions.clear();
        updateBulkActionsPanel();

        try {
            const res = await fetch(`api/preguntas.php?${params.toString()}`);
            const json = await res.json();
            tbody.innerHTML = '';

            if (!json.data || json.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="${colspan}" class="text-center text-muted">${LANG_NO_RESULTS}</td></tr>`;
                updatePaginationInfo(0);
                return;
            }

            const asigMap = {1:'Mates', 2:'Lengua', 3:'Historia', 4:'Ciencias'};
            json.data.forEach(q => {
                let ownerInfo = '';
                if (isSuperAdmin || isAcademy) {
                    ownerInfo = `<td><span class="badge-owner">${q.nombre_propietario}</span></td>`;
                }

                const isGlobal = q.rol_propietario == 1;
                const globalBadge = isGlobal ? `<span class="badge" style="background:var(--primary); color:white; padding:1px 6px; border-radius:10px; font-size:0.65rem; margin-left:5px; font-weight:bold;">GLOBAL</span>` : '';
                const sharedIcon = q.compartida == 1 ? `<span style="color:var(--primary); margin-left:5px;"><i class="fa-solid fa-share-nodes"></i></span>` : '';
                const isChecked = selectedQuestions.has(q.id_pregunta) ? 'checked' : '';

                let actions = '';
                if (currentQFilter === 'trash') {
                    actions = `<button class="btn-icon" style="color:var(--success-color);" onclick="restoreQuestion(${q.id_pregunta})"><i class="fa-solid fa-trash-arrow-up"></i></button>`;
                } else if (currentScope === 'shared_bank' && q.id_propietario != <?php echo $_SESSION['user_id']; ?>) {
                    actions = `<button class="btn-icon" onclick="duplicateQuestion(${q.id_pregunta})" title="Duplicar" style="color:var(--primary);"><i class="fa-solid fa-copy"></i></button>`;
                } else {
                    actions = `
                        <button class="btn-icon" onclick="reassignOne(${q.id_pregunta})"><i class="fa-solid fa-user-pen"></i></button>
                        <button class="btn-icon" onclick="editQuestion(${q.id_pregunta})"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn-icon" onclick="duplicateQuestion(${q.id_pregunta})"><i class="fa-solid fa-copy"></i></button>
                        <button class="btn-icon" style="color:var(--danger-color);" onclick="deleteQuestion(${q.id_pregunta})"><i class="fa-solid fa-trash"></i></button>
                    `;
                }

                tbody.innerHTML += `
                
                <tr>
                    <td class="text-center"><input type="checkbox" class="row-select" onchange="updateSelection(this, ${q.id_pregunta})" ${isChecked}></td>
                    <td><small class="text-muted">${q.id_pregunta}</small></td>
                    <td><div style="max-width:300px;"><strong>${q.texto}</strong> ${globalBadge} ${sharedIcon}</div></td>
                    <td><span style="text-transform:uppercase; font-size:0.8rem;">${q.idioma}</span></td>
                    <td><small>${q.nivel_educativo || '-'}</small></td>
                    <td><small>${q.seccion || '-'}</small></td>
                    <td><small>${q.id_asignatura || '-'}</small></td>
                    <td class="text-center">
                        <span class="diff-badge diff-${q.dificultad}" title="Dificultad ${q.dificultad}">
                            ${q.dificultad}
                        </span>
                    </td>
                    <td>
                        <div class="tags-container">
                            ${q.etiquetas ? q.etiquetas.split(',').map(tag => `<span class="tag-pill"><i class="fa-solid fa-tag"></i>${tag.trim()}</span>`).join('') : '-'}
                        </div>
                    </td>
                    ${ownerInfo}
                                    <td class="text-right">${actions}</td>
                                </tr>`;
                            });
            updatePaginationInfo(json.total);
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="${colspan}" class="text-center text-danger">Error</td></tr>`;
        }
    }

    function updatePaginationInfo(total) {
        qState.totalPages = Math.ceil(total / qState.limit) || 1;
        const txt = `<?php echo __('key_pagination_page'); ?> ${qState.page} <?php echo __('key_pagination_of'); ?> ${qState.totalPages}`;
        document.getElementById('pageInfoTop').innerText = txt;
        document.getElementById('pageInfoBottom').innerText = txt;
        document.getElementById('totalRecordsInfo').innerText = `<?php echo __('key_pagination_total'); ?> ${total}`;
        ['btnPrevTop', 'btnPrevBottom'].forEach(id => document.getElementById(id).disabled = (qState.page === 1));
        ['btnNextTop', 'btnNextBottom'].forEach(id => document.getElementById(id).disabled = (qState.page >= qState.totalPages));
    }

    // --- FILTROS ---
    function applyQFilter(type, btn) {
        document.querySelectorAll('.btn-quick-filter').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('qSearch').value = '';
        document.getElementById('f_seccion').value = '';
        currentQFilter = '';
        if(type === 'most_used') { qState.sort = 'veces_usada'; qState.order = 'DESC'; currentScope = 'mine'; } 
        else if (type === 'orphan') { currentQFilter = 'orphan'; currentScope = 'mine'; } 
        else if (type === 'shared') { currentScope = 'shared_bank'; }
        else if (type === 'trash') { currentQFilter = 'trash'; currentScope = 'mine'; } // <--- Línea añadida
        if(document.getElementById('qFilterScope')) document.getElementById('qFilterScope').value = (currentScope === 'shared_bank' ? 'shared_bank' : 'mine');
        qState.page = 1;
        loadQuestions();
    }

    function resetQFilters(btn) {
        qState.sort = 'texto';
        qState.order = 'ASC';
        currentQFilter = '';
        currentScope = 'mine';
        if (document.getElementById('qFilterScope')) document.getElementById('qFilterScope').value = 'mine';
        loadQuestions();
        document.querySelectorAll('.btn-quick-filter').forEach(b => b.classList.remove('active'));
    }

    // --- CRUD ---
    async function editQuestion(id) {
        // Refrescamos las listas para que el datalist tenga datos actualizados
        refreshSubjectsList();
        refreshSectionsList();
        try {
            const res = await fetch(`api/preguntas.php?action=get_one&id=${id}`);
            const json = await res.json();
            if (json.error) {
                showToast(json.error, 'error');
                return;
            }
            const q = json.data;
            document.getElementById('formAction').value = 'update_manual';
            document.getElementById('editId').value = q.id_pregunta;
            document.getElementById('inp_texto').value = q.texto;
            document.getElementById('inp_seccion').value = q.seccion;
            document.getElementById('inp_idioma').value = q.idioma;
            document.getElementById('inp_asignatura').value = q.id_asignatura || '';
            document.getElementById('inp_nivel').value = q.nivel_educativo || '';
            document.getElementById('inp_dificultad').value = q.dificultad || 3;
            document.getElementById('inp_etiquetas').value = q.etiquetas || '';
            document.getElementById('manual_tipo').value = q.tipo;
            document.getElementById('inp_tiempo').value = q.tiempo_limite;
            document.getElementById('chk_doble').checked = (q.doble_valor == 1);
            if (document.getElementById('manual_target_user')) document.getElementById('manual_target_user').value = q.id_propietario;
            renderManualOptions(JSON.parse(q.json_opciones));
            document.getElementById('btnSaveManual').innerText = "<?php echo __('key_js_btn_update'); ?>";
            document.getElementById('btn-tab-import').style.display = 'none';
            document.getElementById('btn-tab-manual').click();
            showSection('create');
        } catch (e) {
            showToast("Error", 'error');
        }
    }

    function cancelEdit() {
        prepareCreate();
        showSection('list');
    }

    function prepareCreate() {
        document.getElementById('frmManual').reset();
        document.getElementById('formAction').value = 'create_manual';
        document.getElementById('btnSaveManual').innerText = "<?php echo __('key_btn_save'); ?>";
        document.getElementById('btn-tab-import').style.display = 'block';
        renderManualOptions();
        showSection('create');
        refreshSubjectsList();
        refreshSectionsList();
    }

    async function validateQFile() {
        // Limpiamos estados previos
        document.getElementById('import-step-1').classList.add('hidden');
        document.getElementById('import-mapping-step').classList.add('hidden');
        document.getElementById('import-step-2').classList.add('hidden');
        
        const fd = new FormData();
        fd.append('action', 'validate_import');
        fd.append('archivo', fileToImport);

        try {
            showToast("Analizando archivo...", "info");
            const res = await fetch('api/preguntas.php', { method: 'POST', body: fd });
            const json = await res.json();

            if (json.status === 'need_mapping') {
                // Ir al paso de mapeo
                renderMappingInterface(json.headers);
                document.getElementById('st-1').classList.remove('active');
                document.getElementById('st-2').classList.add('active');
            } else if (json.status === 'error') {
                showToast(json.mensaje, "error");
                resetImport();
            }
        } catch (e) {
            showToast("Error en la validación", "error");
        }
    }

    async function executeQImport() {
        if (!fileToImport) return;
        const fd = new FormData();
        fd.append('action', 'execute_import');
        fd.append('archivo', fileToImport);
        
        const mapping = {};
        document.querySelectorAll('.mapping-select').forEach(sel => {
            if (sel.value !== "") mapping[sel.getAttribute('data-key')] = parseInt(sel.value);
        });
        fd.append('mapping', JSON.stringify(mapping));
        
        const target = document.getElementById('import_target_user');
        if (target) fd.append('target_user_id', target.value);

        try {
            showToast("Importando preguntas...", "info");
            const res = await fetch('api/preguntas.php', { method: 'POST', body: fd });
            const json = await res.json();
            if (json.status === 'ok') {
                showToast(json.mensaje);
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(json.mensaje, "error");
            }
        } catch (e) {
            showToast("Error en la importación", "error");
        }
    }

    function renderMappingInterface(headers) {
        const tbody = document.getElementById('mappingTableBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        const fields = [
            { key: 'texto', label: 'Pregunta *', match: ['texto_pregunta', 'texto'] },
            { key: 'correcta', label: 'Respuesta Correcta *', match: ['respuesta_correcta', 'correcta'] },
            { key: 'a', label: 'Opción A *', match: ['opcion_a', 'a'] },
            { key: 'b', label: 'Opción B *', match: ['opcion_b', 'b'] },
            { key: 'c', label: 'Opción C', match: ['opcion_c', 'c'] },
            { key: 'd', label: 'Opción D', match: ['opcion_d', 'd'] },
            { key: 'seccion', label: 'Sección', match: ['seccion'] },
            { key: 'idioma', label: 'Idioma', match: ['idioma'] },
            { key: 'tiempo', label: 'Tiempo Límite', match: ['tiempo_limite'] },
            { key: 'doble', label: 'Puntos Dobles', match: ['doble_valor'] },
            { key: 'asignatura', label: 'Asignatura', match: ['asignatura'] },
            { key: 'nivel', label: 'Nivel', match: ['nivel'] },
            { key: 'dificultad', label: 'Dificultad', match: ['dificultad'] },
            { key: 'etiquetas', label: 'Etiquetas', match: ['etiquetas'] }
        ];

        fields.forEach(f => {
            let opts = `<option value="">-- Ignorar --</option>`;
            headers.forEach((h, i) => {
                const hClean = h.toLowerCase().trim();
                const selected = f.match.some(m => hClean === m) ? 'selected' : '';
                opts += `<option value="${i}" ${selected}>${h}</option>`;
            });
            tbody.innerHTML += `<tr><td style="padding:8px;"><strong>${f.label}</strong></td><td><select class="form-control mapping-select" data-key="${f.key}">${opts}</select></td></tr>`;
        });
        document.getElementById('import-mapping-step').classList.remove('hidden');
    }

    function resetImport() {
        location.reload();
    }

    // Acciones en Lote
    function updateSelection(checkbox, id) {
        if (checkbox.checked) selectedQuestions.add(id);
        else selectedQuestions.delete(id);
        updateBulkActionsPanel();
    }

    function toggleSelectAll(master) {
        document.querySelectorAll('#questionsTableBody .row-select').forEach(cb => {
            cb.checked = master.checked;
            updateSelection(cb, parseInt(cb.getAttribute('onchange').match(/\d+/)[0]));
        });
    }

    function updateBulkActionsPanel() {
        const count = selectedQuestions.size;
        document.getElementById('bulkActionsPanel').style.display = count > 0 ? 'block' : 'none';
        document.getElementById('selectionCount').innerText = count;
    }

    function openReassignModal() {
        document.getElementById('reassignModal').classList.add('active');
        document.getElementById('modalReassignCount').innerText = selectedQuestions.size;
    }
    async function handleReassignSubmit(e) {
    e.preventDefault();
    bulkAction('reassign');
    }

async function bulkAction(type) {
    if (type === 'delete' && !confirm(LANG_CONFIRM_DELETE)) return;
    const ids = Array.from(selectedQuestions);
    const target = (type === 'reassign') ? document.getElementById('reassign_target_user').value : null;
    const res = await fetch('api/preguntas.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'bulk_action', type, ids, target })
    });
    const json = await res.json();
    if (json.success) {
        showToast("Operación completada");
        selectedQuestions.clear();
        document.getElementById('reassignModal').classList.remove('active');
        loadQuestions();
    }
}

    async function deleteQuestion(id) {
        if (confirm(LANG_CONFIRM_DELETE)) {
            try {
                const res = await fetch('api/preguntas.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', id_pregunta: id })
                });
                const json = await res.json();
                if (json.success) {
                    showToast("Pregunta movida a la papelera"); // Mensaje informativo
                    loadQuestions(); // Recargar la lista
                } else {
                    showToast(json.error || "Error al borrar", "error");
                }
            } catch (e) {
                showToast("Error de conexión", "error");
            }
        }
    }

    async function duplicateQuestion(id) {
        await fetch('api/preguntas.php', {
            method: 'POST',
            body: JSON.stringify({
                action: 'duplicate',
                id_pregunta: id
            })
        });
        loadQuestions();
    }
    
    async function loadUsersForSelect() {
        try {
            const res = await fetch('api/usuarios.php?limit=-1');
            const json = await res.json();
            
            // 1. Filtro de búsqueda por propietario
            const sel = document.getElementById('f_usuario');
            if (sel) json.data.forEach(u => sel.innerHTML += `<option value="${u.id_usuario}">${u.nombre}</option>`);
            
            // 2. Modal de reasignación
            const sel2 = document.getElementById('reassign_target_user');
            if (sel2) json.data.forEach(u => sel2.innerHTML += `<option value="${u.id_usuario}">${u.nombre}</option>`);
            
            // 3. Destino en creación manual
            const sel3 = document.getElementById('manual_target_user');
            if (sel3) json.data.forEach(u => {
                if(u.id_usuario != <?php echo $_SESSION['user_id']; ?>) {
                    sel3.innerHTML += `<option value="${u.id_usuario}">${u.nombre}</option>`;
                }
            });

            // 4. Destino en IMPORTACIÓN (Lo que faltaba)
            const sel4 = document.getElementById('import_target_user');
            if (sel4) {
                // Añadimos el resto de usuarios al select de importación
                json.data.forEach(u => {
                    if(u.id_usuario != <?php echo $_SESSION['user_id']; ?>) {
                        sel4.innerHTML += `<option value="${u.id_usuario}">${u.nombre}</option>`;
                    }
                });
            }

            // Cargar las asignaturas en el filtro
            const resAsig = await fetch('api/preguntas.php?action=list_subjects');
            const jsonAsig = await resAsig.json();
            const selAsig = document.getElementById('f_asignatura');
            if (selAsig && jsonAsig.data) {
                jsonAsig.data.forEach(asig => {
                    selAsig.innerHTML += `<option value="${asig}">${asig}</option>`;
                });
            }
        } catch (e) {
            console.error("Error en loadUsersForSelect:", e);
        }
    }

    async function restoreQuestion(id) {
        const res = await fetch('api/preguntas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'restore', id_pregunta: id })
        });
        const json = await res.json();
        if (json.success) {
            showToast("Pregunta restaurada");
            loadQuestions();
        }
    }

    function reassignOne(id) {
        selectedQuestions.clear();
        selectedQuestions.add(id);
        openReassignModal();
    }

    async function restoreQuestion(id) {
        const res = await fetch('api/preguntas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'restore', id_pregunta: id })
        });
        const json = await res.json();
        if (json.success) {
            showToast("Pregunta restaurada");
            loadQuestions();
        }
    }

    async function refreshSubjectsList() {
        try {
            const res = await fetch('api/preguntas.php?action=list_subjects');
            const json = await res.json();
            const dl = document.getElementById('subjectsList');
            if (dl && json.data) {
                dl.innerHTML = '';
                json.data.forEach(s => {
                    dl.innerHTML += `<option value="${s}">`;
                });
            }
        } catch(e) {
            console.error("Error cargando asignaturas:", e);
        }
    }

    async function refreshSectionsList() {
        try {
            const res = await fetch('api/preguntas.php?action=list_sections');
            const json = await res.json();
            const dl = document.getElementById('sectionsList');
            if (dl && json.data) {
                dl.innerHTML = '';
                json.data.forEach(s => {
                    dl.innerHTML += `<option value="${s}">`;
                });
            }
        } catch(e) {
            console.error("Error cargando secciones:", e);
        }
    }

    function clearAllFilters() {
        const filters = [
            'qSearch', 'f_asignatura', 'f_nivel', 'f_taxonomia', 
            'f_dificultad', 'f_idioma', 'f_seccion', 
            'f_date_from', 'f_date_to'
        ];
        filters.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        if (document.getElementById('f_usuario')) document.getElementById('f_usuario').value = '';
        
        qState.page = 1;
        loadQuestions();
        showToast("Filtros limpiados");
    }
</script>