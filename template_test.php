<?php
/**
 * Plugin Name: Template Installer (Elementor Free)
 * Description: Importa páginas completas hechas en Elementor desde JSON y las abre para editar.
 * Version: 1.0.0
 */

if (!defined('ABSPATH'))
    exit;

class Template_test
{
    const SLUG = 'noble-template-installer';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_post_noble_import_page', [$this, 'handle_import']);
    }

    public function admin_menu()
    {
        add_menu_page(
            'Noble Templates',
            'Noble Templates',
            'manage_options',
            self::SLUG,
            [$this, 'render_admin_page'],
            'dashicons-layout',
            58
        );
    }

    private function templates()
    {
        return [
            [
                'id' => 'landing-01',
                'name' => 'Landing 01 (Página completa)',
                'desc' => 'Landing base con hero, CTA y secciones listas para editar en Elementor.',
                'file' => plugin_dir_path(__FILE__) . 'templates/landing-01.json',
                'thumb' => plugin_dir_url(__FILE__) . 'assets/landing-01.jpg',
            ],
        ];
    }


    public function render_admin_page()
    {
        if (!current_user_can('manage_options'))
            return;

        $elementor_ok = did_action('elementor/loaded');

        echo '<div class="wrap">';
        echo '<h1 style="margin-bottom:10px;">Noble Templates</h1>';
        echo '<p style="color:#646970;margin-top:0;">Importa páginas completas y edítalas en Elementor.</p>';

        if (!$elementor_ok) {
            echo '<div class="notice notice-error"><p><b>Elementor no está activo.</b> Actívalo para poder importar plantillas.</p></div>';
        }

        // CSS + JS inline (simple para MVP)
        ?>
          <style>
            .noble-grid{
              display:grid;
              grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
              gap:16px;
              margin-top:16px;
            }
            .noble-card{
              background:#fff;
              border:1px solid #dcdcde;
              border-radius:14px;
              overflow:hidden;
              box-shadow: 0 1px 2px rgba(0,0,0,.04);
              display:flex;
              flex-direction:column;
            }
            .noble-thumb{
              position:relative;
              width:100%;
              aspect-ratio: 16 / 9;
              background:#f6f7f7;
              overflow:hidden;
              cursor:pointer;
            }
            .noble-thumb img{
              width:100%;
              height:100%;
              object-fit:cover;
              display:block;
              transition: transform .25s ease;
            }
            .noble-thumb:hover img{ transform: scale(1.03); }
            .noble-badge{
              position:absolute;
              top:12px;
              left:12px;
              background:#111827;
              color:#fff;
              font-size:12px;
              padding:6px 10px;
              border-radius:999px;
              opacity:.92;
            }
            .noble-body{
              padding:14px 14px 12px;
              display:flex;
              flex-direction:column;
              gap:10px;
            }
            .noble-title{
              margin:0;
              font-size:16px;
              line-height:1.25;
            }
            .noble-desc{
              margin:0;
              color:#646970;
              font-size:13px;
              line-height:1.45;
            }
            .noble-actions{
              display:flex;
              gap:10px;
              align-items:center;
              margin-top:6px;
            }
            .noble-actions .button{
              height:34px;
              padding:0 12px;
              border-radius:10px;
            }
            .noble-secondary{
              color:#1d2327;
              text-decoration:none;
              font-size:13px;
            }
            /* Modal */
            .noble-modal-backdrop{
              position:fixed;
              inset:0;
              background:rgba(0,0,0,.55);
              display:none;
              align-items:center;
              justify-content:center;
              z-index:99999;
              padding:24px;
            }
            .noble-modal{
              max-width:1100px;
              width:100%;
              background:#fff;
              border-radius:14px;
              overflow:hidden;
              box-shadow: 0 10px 30px rgba(0,0,0,.25);
            }
            .noble-modal-header{
              display:flex;
              justify-content:space-between;
              align-items:center;
              padding:12px 14px;
              border-bottom:1px solid #dcdcde;
            }
            .noble-modal-title{
              margin:0;
              font-size:14px;
              font-weight:600;
              color:#1d2327;
            }
            .noble-modal-close{
              border:none;
              background:transparent;
              font-size:18px;
              cursor:pointer;
              line-height:1;
              padding:6px 10px;
              border-radius:10px;
            }
            .noble-modal-close:hover{ background:#f0f0f1; }
            .noble-modal-img{
              width:100%;
              height:auto;
              display:block;
            }
          </style>

          <div class="noble-grid">
            <?php foreach ($this->templates() as $t):
                $nonce = wp_create_nonce('noble_import_' . $t['id']);
                $thumb = !empty($t['thumb']) ? $t['thumb'] : '';
                $desc = !empty($t['desc']) ? $t['desc'] : '';
                ?>
                  <div class="noble-card">
                    <div class="noble-thumb" 
                         data-noble-preview="<?php echo esc_attr($thumb); ?>"
                         data-noble-title="<?php echo esc_attr($t['name']); ?>"
                         title="Ver preview">
                      <?php if ($thumb): ?>
                            <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($t['name']); ?>">
                      <?php else: ?>
                            <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#8c8f94;">
                              Sin preview
                            </div>
                      <?php endif; ?>
                      <div class="noble-badge">Página completa</div>
                    </div>

                    <div class="noble-body">
                      <h2 class="noble-title"><?php echo esc_html($t['name']); ?></h2>
                      <?php if ($desc): ?>
                            <p class="noble-desc"><?php echo esc_html($desc); ?></p>
                      <?php endif; ?>

                      <div class="noble-actions">
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin:0;">
                          <input type="hidden" name="action" value="noble_import_page">
                          <input type="hidden" name="template_id" value="<?php echo esc_attr($t['id']); ?>">
                          <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                          <button class="button button-primary" <?php echo !$elementor_ok ? 'disabled' : ''; ?>>
                            Importar
                          </button>
                        </form>

                        <?php if ($thumb): ?>
                              <a href="#" class="noble-secondary noble-open-preview">Ver preview</a>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
            <?php endforeach; ?>
          </div>

          <!-- Modal -->
          <div class="noble-modal-backdrop" id="nobleModal">
            <div class="noble-modal" role="dialog" aria-modal="true">
              <div class="noble-modal-header">
                <p class="noble-modal-title" id="nobleModalTitle">Preview</p>
                <button class="noble-modal-close" id="nobleModalClose" aria-label="Cerrar">✕</button>
              </div>
              <img class="noble-modal-img" id="nobleModalImg" src="" alt="">
            </div>
          </div>

          <script>
            (function(){
              const modal = document.getElementById('nobleModal');
              const img = document.getElementById('nobleModalImg');
              const title = document.getElementById('nobleModalTitle');
              const closeBtn = document.getElementById('nobleModalClose');

              function openModal(src, t){
                if(!src) return;
                img.src = src;
                img.alt = t || 'Preview';
                title.textContent = t || 'Preview';
                modal.style.display = 'flex';
              }
              function closeModal(){
                modal.style.display = 'none';
                img.src = '';
              }

              document.querySelectorAll('.noble-thumb').forEach(el=>{
                el.addEventListener('click', (e)=>{
                  const src = el.getAttribute('data-noble-preview');
                  const t = el.getAttribute('data-noble-title');
                  openModal(src, t);
                });
              });

              document.querySelectorAll('.noble-open-preview').forEach(a=>{
                a.addEventListener('click', (e)=>{
                  e.preventDefault();
                  const card = a.closest('.noble-card');
                  const thumb = card.querySelector('.noble-thumb');
                  openModal(thumb.getAttribute('data-noble-preview'), thumb.getAttribute('data-noble-title'));
                });
              });

              closeBtn.addEventListener('click', closeModal);
              modal.addEventListener('click', (e)=>{
                if(e.target === modal) closeModal();
              });

              document.addEventListener('keydown', (e)=>{
                if(e.key === 'Escape') closeModal();
              });
            })();
          </script>
          <?php

          echo '</div>'; // wrap
    }


    public function handle_import()
    {
        if (!current_user_can('manage_options'))
            wp_die('No autorizado.');

        $template_id = isset($_POST['template_id']) ? sanitize_text_field($_POST['template_id']) : '';
        if (!$template_id)
            wp_die('template_id faltante.');

        check_admin_referer('noble_import_' . $template_id);

        if (!did_action('elementor/loaded')) {
            wp_die('Elementor no está activo.');
        }

        $tpl = null;
        foreach ($this->templates() as $t) {
            if ($t['id'] === $template_id) {
                $tpl = $t;
                break;
            }
        }
        if (!$tpl)
            wp_die('Plantilla no encontrada.');
        if (!file_exists($tpl['file']))
            wp_die('Archivo JSON no existe.');

        $raw = file_get_contents($tpl['file']);
        $json = json_decode($raw, true);
        if (!is_array($json))
            wp_die('JSON inválido.');

        // Elementor export suele traer: content, page_settings, version, title
        $content = $json['content'] ?? null;
        if (!$content)
            wp_die('JSON sin "content". Exporta la plantilla correctamente desde Elementor.');

        $page_settings = is_array($json['page_settings'] ?? null) ? $json['page_settings'] : [];

        $title = !empty($json['title']) ? sanitize_text_field($json['title']) : ('Página - ' . $tpl['name']);

        // Crea página como borrador
        $page_id = wp_insert_post([
            'post_title' => $title,
            'post_type' => 'page',
            'post_status' => 'draft',
            'post_content' => '',
        ], true);

        if (is_wp_error($page_id))
            wp_die($page_id->get_error_message());

        // Guarda data de Elementor
        update_post_meta($page_id, '_elementor_edit_mode', 'builder');
        update_post_meta($page_id, '_elementor_data', wp_json_encode($content));
        update_post_meta($page_id, '_elementor_page_settings', $page_settings); // array real

        // Opcional: plantilla de página (depende del tema; puedes quitarlo si no te sirve)
        // update_post_meta($page_id, '_wp_page_template', 'elementor_canvas');

        // Redirige directo al editor de Elementor
        $url = admin_url('post.php?post=' . $page_id . '&action=elementor');
        wp_redirect($url);
        exit;
    }
}

new Template_test();
