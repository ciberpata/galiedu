<?php
// views/perfil.php
$db = (new Database())->getConnection();
$uid = $_SESSION['user_id'];
$urole = $_SESSION['user_role'];

// DEFINICIONES GLOBALES PARA EVITAR WARNINGS EN EL LOG
$humanAvatars = [1=>'👨', 2=>'👩', 3=>'👧', 4=>'👦', 5=>'👴', 6=>'👵', 7=>'🤴', 8=>'👸', 9=>'🧔', 10=>'👳', 11=>'👱', 12=>'👰', 13=>'👲', 14=>'👽', 15=>'🤖'];
$hats = [0 => '', 1 => '🎓', 2 => '👑', 3 => '🎧'];

$sql = "SELECT u.*, df.razon_social, df.nombre_negocio, df.nif, df.roi, df.telefono, df.direccion, df.direccion_numero, df.cp, df.id_pais, df.id_provincia, df.id_ciudad FROM usuarios u LEFT JOIN datos_fiscales df ON u.id_usuario = df.id_usuario WHERE u.id_usuario = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
?>

<div id="toast"></div>

<div class="card">
    <div class="tabs-header">
        <button class="tab-btn active" onclick="openTab('edit-profile', this)"><?php echo __('profile_title'); ?></button>
        <?php if ($urole == 6): ?>
            <button class="tab-btn" onclick="openTab('game-profile', this)">🎮 Perfil de Juego</button>
        <?php endif; ?>
        <button class="tab-btn" onclick="openTab('appearance', this)"><?php echo __('appearance'); ?></button>
        <?php if ($urole == 1 || $urole == 2 || $urole == 4): ?>
            <button class="tab-btn" onclick="openTab('white-label', this)">Marca Blanca</button>
        <?php endif; ?>
        <button class="tab-btn" onclick="openTab('change-pass', this)"><?php echo __('security'); ?></button>
    </div>

    <div id="edit-profile" class="tab-content active">
        <form id="profileForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_profile">
            <div class="<?php echo $urole == 6 ? '' : 'profile-layout'; ?>">
                <?php if ($urole != 6): ?>
                <div class="avatar-section">
                    <img id="avatar-preview" class="avatar-img" src="<?php echo !empty($user['foto_perfil']) ? $user['foto_perfil'] . '?t=' . time() : 'assets/img/default-avatar.png'; ?>">
                    <div style="width: 100%;">
                        <label for="avatar_file" class="btn-primary" style="width:100%; cursor:pointer;">
                            <i class="fa-solid fa-camera"></i> <?php echo __('change_photo'); ?>
                        </label>
                        <input type="file" id="avatar_file" name="foto_perfil" accept="image/*" style="display:none;" onchange="previewImage(event)">
                    </div>
                </div>
                <?php endif; ?>

                <div>
                    <h3 class="modal-section-title"><?php echo __('personal_data'); ?></h3>
                    <?php if ($urole == 6): ?>
                        <div style="background: rgba(var(--primary-rgb), 0.1); padding: 10px; border-radius: 8px; border-left: 4px solid var(--primary); margin-bottom: 15px;">
                            <p style="margin: 0; color: var(--primary); font-size: 0.9rem;">
                                <i class="fa-solid fa-info-circle"></i> Tus datos personales son gestionados por tu profesor.
                            </p>
                        </div>
                    <?php endif; ?>

                    <div class="modal-form-grid">
                        <div class="mb-4">
                            <label class="block text-muted mb-2"><?php echo __('name_label'); ?> <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($user['nombre'] ?? ''); ?>" <?php echo $urole == 6 ? 'readonly style="background:var(--bg-body); cursor:not-allowed;"' : 'required'; ?>>
                        </div>
                        <div class="mb-4">
                            <label class="block text-muted mb-2"><?php echo __('email_label'); ?></label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['correo'] ?? ''); ?>" disabled style="background:var(--bg-body);">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-muted mb-2"><?php echo __('language_label'); ?> <span class="text-danger">*</span></label>
                        <select name="idioma_pref" class="form-control" required>
                            <option value="es" <?php echo ($user['idioma_pref'] ?? 'es') == 'es' ? 'selected' : ''; ?>><?php echo __('lang_es'); ?></option>
                            <option value="gl" <?php echo ($user['idioma_pref'] ?? 'es') == 'gl' ? 'selected' : ''; ?>><?php echo __('lang_gl'); ?></option>
                            <option value="en" <?php echo ($user['idioma_pref'] ?? 'es') == 'en' ? 'selected' : ''; ?>><?php echo __('lang_en'); ?></option>
                        </select>
                    </div>

                    <?php if ($urole != 6): ?>
                        <h3 class="modal-section-title mt-4"><?php echo __('fiscal_data'); ?></h3>
                        <div class="modal-form-grid">
                            <div class="mb-4"><label class="block text-muted mb-2"><?php echo __('business_name_label'); ?> *</label><input type="text" name="razon_social" class="form-control" value="<?php echo htmlspecialchars($user['razon_social'] ?? ''); ?>" required></div>
                            <div class="mb-4"><label class="block text-muted mb-2"><?php echo __('trade_name_label'); ?></label><input type="text" name="nombre_negocio" class="form-control" value="<?php echo htmlspecialchars($user['nombre_negocio'] ?? ''); ?>"></div>
                        </div>
                        <div class="modal-form-grid">
                            <div class="mb-4"><label class="block text-muted mb-2"><?php echo __('nif_label'); ?> *</label><input type="text" name="nif" id="inputNif" class="form-control" value="<?php echo htmlspecialchars($user['nif'] ?? ''); ?>" style="text-transform:uppercase;" onblur="validateNifVisual(this)" required></div>
                            <div class="mb-4"><label class="block text-muted mb-2"><?php echo __('roi_label'); ?></label><input type="text" name="roi" class="form-control" value="<?php echo htmlspecialchars($user['roi'] ?? ''); ?>" style="text-transform:uppercase;"></div>
                        </div>
                        <div class="mb-4"><label class="block text-muted mb-2"><?php echo __('phone_label'); ?> *</label><input type="tel" name="telefono" class="form-control" value="<?php echo htmlspecialchars($user['telefono'] ?? ''); ?>" required></div>
                        <div class="modal-form-grid">
                            <div class="mb-4"><label class="block text-muted mb-2"><?php echo __('address_label'); ?> *</label><input type="text" name="direccion" class="form-control" value="<?php echo htmlspecialchars($user['direccion'] ?? ''); ?>" required></div>
                            <div class="mb-4"><label class="block text-muted mb-2"><?php echo __('key_users_modal_address_num'); ?> *</label><input type="text" name="direccion_numero" class="form-control" value="<?php echo htmlspecialchars($user['direccion_numero'] ?? ''); ?>" required></div>
                        </div>
                        <div class="modal-form-grid">
                            <div class="mb-4"><label class="block text-muted mb-2"><?php echo __('country_label'); ?> *</label><select name="id_pais" id="selectPais" class="form-control" onchange="toggleAddressFields()" required></select></div>
                            <div class="mb-4"><label class="block text-muted mb-2"><?php echo __('zip_code_label'); ?> *</label><input type="text" name="cp" class="form-control" value="<?php echo htmlspecialchars($user['cp'] ?? ''); ?>" required></div>
                        </div>
                        <div id="spainFields" class="hidden modal-form-grid">
                            <div class="mb-4"><label class="block text-muted mb-2"><?php echo __('province_label'); ?> *</label><select name="id_provincia" id="selectProvincia" class="form-control" onchange="loadCiudades(this.value)">
                                    <option value=""><?php echo __('key_users_modal_select'); ?></option>
                                </select></div>
                            <div class="mb-4"><label class="block text-muted mb-2"><?php echo __('city_label'); ?> *</label><select name="id_ciudad" id="selectCiudad" class="form-control"></select></div>
                        </div>
                        <div class="text-right mt-4" style="display: flex; justify-content: flex-end; gap: 10px;">
                            <button type="button" class="btn-icon" onclick="resetBrandingColors()" title="Volver a colores originales">
                                <i class="fa-solid fa-arrow-rotate-left"></i> Restablecer Colores
                            </button>
                            <button type="submit" class="btn-primary">Guardar Marca Blanca</button>
                        </div>
                    <?php else: ?>
                        
                <div class="text-right mt-4"><button type="submit" class="btn-primary">Guardar Perfil de Juego</button></div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <?php if ($urole == 6): ?>
    <div id="game-profile" class="tab-content hidden">
        <form id="gameProfileForm" onsubmit="handleSave(event)">
            <input type="hidden" name="action" value="update_profile">
            <h3 class="modal-section-title">Identidad en el Juego</h3>

            <div class="avatar-preview-box" style="border: 2px dashed var(--border-color); border-radius: 15px; padding: 20px; text-align: center; margin-bottom: 20px; background: var(--bg-body); min-height: 180px; display: flex; justify-content: center; align-items: center;">
                <div class="preview-emoji-container">
                    <span id="pAvatar" class="avatar-emoji"><?php echo $humanAvatars[$user['avatar_id'] ?? 1]; ?></span>
                    <span id="pHat" class="avatar-hat">
                        <?php echo $hats[$user['sombrero_id'] ?? 0]; ?>
                    </span>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-muted mb-2">Tu Apodo (Nick)</label>
                <input type="text" name="nick" class="form-control" value="<?php echo htmlspecialchars($user['nick'] ?? ''); ?>" maxlength="15">
            </div>

            <label class="block text-muted mb-2">1. Selecciona tu Avatar</label>
            <div class="avatar-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap: 10px; max-height: 250px; overflow-y: auto; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px;">
                <?php foreach ($humanAvatars as $id => $emoji): $selected = (($user['avatar_id'] ?? 1) == $id); ?>
                    <label style="cursor:pointer; text-align:center; padding:10px; border-radius:8px; border: 2px solid <?php echo $selected ? 'var(--primary)' : 'transparent'; ?>; background: <?php echo $selected ? 'var(--primary-light)' : 'none'; ?>;">
                        <input type="radio" name="avatar_id" value="<?php echo $id; ?>" <?php echo $selected ? 'checked' : ''; ?> style="display:none;" onchange="updateProfilePreview('avatar', '<?php echo $emoji; ?>')">
                        <span style="font-size: 2rem;"><?php echo $emoji; ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <label class="block text-muted mb-2 mt-4">2. Añade un Sombrero</label>
            <div class="hat-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap: 10px; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px;">
                <?php
                $hatEmojis = [0 => '❌', 1 => '🎓', 2 => '👑', 3 => '🎧'];
                foreach ($hatEmojis as $hId => $hEmoji):
                    $hSelected = (($user['sombrero_id'] ?? 0) == $hId);
                ?>
                    <label style="cursor:pointer; text-align:center; padding:10px; border-radius:8px; border: 2px solid <?php echo $hSelected ? 'var(--primary)' : 'transparent'; ?>; background: <?php echo $hSelected ? 'var(--primary-light)' : 'none'; ?>;">
                        <input type="radio" name="sombrero_id" value="<?php echo $hId; ?>" <?php echo $hSelected ? 'checked' : ''; ?> style="display:none;" onchange="updateProfilePreview('hat', '<?php echo ($hId==0?'':$hEmoji); ?>', <?php echo $hId; ?>)">
                        <span style="font-size: 1.8rem;"><?php echo $hEmoji; ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="text-right mt-4">
                <button type="submit" class="btn-primary">Guardar Perfil de Juego</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div id="appearance" class="tab-content hidden">
        <form id="themeForm">
            <input type="hidden" name="action" value="update_profile">
            <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($user['nombre'] ?? ''); ?>">
            <h3 class="modal-section-title"><?php echo __('default_appearance_title'); ?></h3>
            <div class="theme-selector-grid">
                <?php
                $colors = ['210' => __('theme_blue'), '270' => __('theme_purple'), '142' => __('theme_green'), '35' => __('theme_orange'), '0' => __('theme_red'), '300' => __('theme_magenta')];
                $currentHue = !empty($user['tema_pref']) ? $user['tema_pref'] : '210';
                foreach ($colors as $hue => $name):
                ?>
                    <label class="color-option">
                        <input type="radio" name="tema_pref" value="<?php echo $hue; ?>" <?php echo ($hue == $currentHue) ? 'checked' : ''; ?> onchange="previewTheme(this)" style="display:none;">
                        <div class="color-circle-large" style="background:hsl(<?php echo $hue; ?>, 100%, 50%);">&nbsp;</div>
                        <small><?php echo $name; ?></small>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="text-right mt-4"><button type="submit" class="btn-primary"><?php echo __('save_appearance'); ?></button></div>
        </form>
    </div>

    <?php if ($urole == 1 || $urole == 2 || $urole == 4): ?>
    <div id="white-label" class="tab-content hidden">
        <form id="brandingForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_branding">
            <h3 class="modal-section-title">Identidad Corporativa</h3>
            <p class="text-muted mb-4">Personaliza los colores y el logo de tu academia para el proyector y reportes.</p>

            <div class="modal-form-grid">
                <div class="mb-4">
                    <label class="block text-muted mb-2">Color Primario</label>
                    <input type="color" name="mb_color_primario" class="form-control" style="height:45px; padding:5px;" value="<?php echo $user['mb_color_primario'] ?? '#6366f1'; ?>">
                </div>
                <div class="mb-4">
                    <label class="block text-muted mb-2">Color de Contraste</label>
                    <input type="color" name="mb_color_secundario" class="form-control" style="height:45px; padding:5px;" value="<?php echo $user['mb_color_secundario'] ?? '#4f46e5'; ?>">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-muted mb-2">Logo de la Academia</label>
                <input type="file" name="mb_logo_marca" class="form-control" accept="image/*">
                <small class="text-muted">Se recomienda formato PNG transparente o WebP.</small>
            </div>

            <div class="mb-4">
                <label class="block text-muted mb-2">Imagen de Fondo Proyector</label>
                <input type="file" name="mb_bg_proyector" class="form-control" accept="image/*">
                <small class="text-muted">Resolución recomendada: 1920x1080px.</small>
            </div>

            <div class="mb-4">
                <label class="block text-muted mb-2">Mensaje de Bienvenida (Lobby)</label>
                <input type="text" name="mb_txt_bienvenida" class="form-control" value="<?php echo htmlspecialchars($user['mb_txt_bienvenida'] ?? '¡Bienvenidos!'); ?>" maxlength="100">
            </div>

            <div class="text-right mt-4" style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-icon" onclick="resetBrandingColors()" title="Restablecer colores originales">
                    <i class="fa-solid fa-arrow-rotate-left"></i> Restablecer
                </button>
                <button type="submit" class="btn-primary">Guardar Marca Blanca</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div id="change-pass" class="tab-content hidden">
        <form id="passForm">
            <input type="hidden" name="action" value="update_profile">
            <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($user['nombre'] ?? ''); ?>">
            <h3 class="modal-section-title"><?php echo __('security'); ?></h3>
            <div class="mb-4" style="max-width:400px; width:100%;">
                <label class="block text-muted mb-2"><?php echo __('new_password_label'); ?></label>
                <input type="password" name="new_password" class="form-control" required minlength="6" placeholder="******">
            </div>
            <button type="submit" class="btn-primary"><?php echo __('update_button'); ?></button>
        </form>
    </div>
</div>

<script>
    function showToast(msg, type = 'success') {
        const x = document.getElementById("toast");
        x.innerText = msg;
        x.className = "show " + type;
        setTimeout(() => {
            x.className = x.className.replace("show", "");
        }, 3000);
    }

    function openTab(id, btn) {
        document.querySelectorAll('.tab-content').forEach(d => d.classList.add('hidden'));
        document.getElementById(id).classList.remove('hidden');
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }

    function isValidNif(nif) {
        if (!nif) return false;
        const validChars = 'TRWAGMYFPDXBNJZSQVHLCKE';
        const nifRexp = /^[0-9]{8}[TRWAGMYFPDXBNJZSQVHLCKE]$/i;
        const nieRexp = /^[XYZ][0-9]{7}[TRWAGMYFPDXBNJZSQVHLCKE]$/i;
        const str = nif.toString().toUpperCase();
        if (!nifRexp.test(str) && !nieRexp.test(str)) return false;
        const nie = str.replace(/^[X]/, '0').replace(/^[Y]/, '1').replace(/^[Z]/, '2');
        const letter = str.substr(-1);
        const charIndex = parseInt(nie.substr(0, 8)) % 23;
        return validChars.charAt(charIndex) === letter;
    }

    function validateNifVisual(input) {
        const errorMsg = document.getElementById('nifError');
        if (!input.value || document.getElementById('selectPais').value !== 'ES') {
            if (errorMsg) errorMsg.classList.add('hidden');
            input.style.borderColor = '';
            return;
        }
        const valid = isValidNif(input.value);
        input.style.borderColor = valid ? 'var(--success-color)' : 'var(--danger-color)';
        if (errorMsg) {
            if (valid) errorMsg.classList.add('hidden');
            else errorMsg.classList.remove('hidden');
        }
    }
    const handleSave = async (e) => {
        // Prevenimos el refresco de página
        if (e && e.preventDefault) e.preventDefault();
        
        // Obtenemos el formulario correctamente
        const form = (e && e.target && e.target.tagName === 'FORM') ? e.target : 
                     (e && e.tagName === 'FORM') ? e : 
                     (e && e.form) ? e.form : null;
        
        if (!form) {
            console.error("No se encontró el formulario");
            return;
        }

        const formData = new FormData(form);
        
        try {
            const res = await fetch('api/usuarios.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if (data.success) {
                showToast("<?php echo __('key_js_saved'); ?>", 'success');
                // Recargamos para que el avatar de la cabecera de la web se actualice
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(data.error || 'Error al guardar', 'error');
            }
        } catch (err) {
            console.error(err);
            showToast('Error de conexión con el servidor', 'error');
        }
    };
    document.querySelectorAll('form').forEach(f => f.addEventListener('submit', handleSave));

    function previewTheme(radio) {
        document.documentElement.style.setProperty('--hue', radio.value);
    }

    function previewImage(event) {
        const r = new FileReader();
        r.onload = function() {
            document.getElementById('avatar-preview').src = r.result;
        };
        r.readAsDataURL(event.target.files[0]);
    }

    const savedPais = "<?php echo $user['id_pais'] ?? 'ES'; ?>";
    const savedProv = "<?php echo $user['id_provincia'] ?? ''; ?>";
    const savedCity = "<?php echo $user['id_ciudad'] ?? ''; ?>";
    async function loadPaises() {
        try {
            const res = await fetch('api/geo.php?type=paises');
            const data = await res.json();
            const sel = document.getElementById('selectPais');
            sel.innerHTML = '<option value=""><?php echo __('key_users_modal_select'); ?></option>';
            data.forEach(p => sel.innerHTML += `<option value="${p.id}" ${p.id === savedPais ? 'selected' : ''}>${p.nombre}</option>`);
            toggleAddressFields();
        } catch (e) {}
    }

    function toggleAddressFields() {
        const isES = document.getElementById('selectPais').value === 'ES';
        const div = document.getElementById('spainFields');
        if (isES) {
            div.classList.remove('hidden');
            loadProvincias();
            document.getElementById('selectProvincia').required = true;
            document.getElementById('selectCiudad').required = true;
        } else {
            div.classList.add('hidden');
            document.getElementById('selectProvincia').required = false;
            document.getElementById('selectCiudad').required = false;
        }
    }
    async function loadProvincias() {
        const res = await fetch('api/geo.php?type=provincias&id=ES');
        const data = await res.json();
        const sel = document.getElementById('selectProvincia');
        sel.innerHTML = '<option value=""><?php echo __('key_users_modal_select'); ?></option>';
        data.forEach(p => sel.innerHTML += `<option value="${p.id}" ${p.id == savedProv ? 'selected' : ''}>${p.nombre}</option>`);
        if (savedProv) loadCiudades(savedProv);
    }
    async function loadCiudades(pid) {
        if (!pid) return;
        const res = await fetch(`api/geo.php?type=ciudades&id=${pid}`);
        const data = await res.json();
        const sel = document.getElementById('selectCiudad');
        sel.innerHTML = '<option value=""><?php echo __('key_users_modal_select'); ?></option>';
        data.forEach(c => sel.innerHTML += `<option value="${c.id}" ${c.id == savedCity ? 'selected' : ''}>${c.nombre}</option>`);
    }
    document.addEventListener('DOMContentLoaded', loadPaises);

    function resetBrandingColors() {
        const form = document.getElementById('brandingForm');
        if (form) {
            // Ponemos los colores por defecto de la App
            form.querySelector('input[name="mb_color_primario"]').value = '#6366f1';
            form.querySelector('input[name="mb_color_secundario"]').value = '#4f46e5';
            showToast('Colores restablecidos. Pulsa Guardar para aplicar.', 'success');
        }
    }

    function updateProfilePreview(type, emoji, id = 0) {
        if(type === 'avatar') {
            document.getElementById('pAvatar').innerText = emoji;
        } else {
            const hat = document.getElementById('pHat');
            hat.innerText = (id == 0) ? '' : emoji;
            hat.classList.remove('animating');
            void hat.offsetWidth;
            if(id > 0) hat.classList.add('animating');
        }
    }
</script>