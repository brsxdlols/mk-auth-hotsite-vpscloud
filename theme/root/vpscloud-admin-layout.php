<?php
// Runs only as a hook around the native authenticated layout page.
function vpscloud_admin_layout_begin() {
    if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') !== 'hotsite_layout.hhvm') return;
    header('X-VPSCloud-Layout: enabled');
    $request = null;
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['vpscloud_visual_save'])) {
        $request = $_POST;
        $_POST = []; $_REQUEST = $_GET; $_SERVER['REQUEST_METHOD'] = 'GET';
    }
    require_once __DIR__.'/vpscloud-db.php';
    require_once __DIR__.'/vpscloud-layout.php';

    ob_start(function($html) use ($request) {
        // The native page must have passed its own authentication and rendered the layout form.
        if (!preg_match('~<select\\b[^>]*>.*?layout-vpscloud-(?:sistema|whatsapp).*?</select>~is', $html)
            || session_id() === '') return $html;
        try {
            // Reuse the connection opened by MK-Auth after its authentication.
            // Loading conexao.php before the native page hides its variables in local scope.
            $db=null;
            foreach($GLOBALS as $candidate) {
                if($candidate instanceof mysqli && !$candidate->connect_errno){$db=$candidate;break;}
            }
            if(!$db && defined('CONHOSTNAME') && defined('CONUSERNAME') && defined('CONPASSWRD') && defined('CONDATABASE')) {
                $db=new mysqli(CONHOSTNAME,CONUSERNAME,CONPASSWRD,CONDATABASE);
            }
            if(!$db || $db->connect_errno)throw new RuntimeException('Conexão nativa indisponível.');
            require_once __DIR__.'/vpscloud-db.php';
            require_once __DIR__.'/vpscloud-layout.php';

            $csrf=hash('sha256','vpscloud-visual|'.session_id());
            $notice='';
            if ($request !== null) {
                if (!is_string($request['csrf'] ?? null) || !hash_equals($csrf,$request['csrf'])) {
                    $notice='Não foi possível validar a solicitação. Atualize a página e tente novamente.';
                } else {
                    vpscloud_set_visual_mode($db,$request['visual_mode'] ?? '');
                    $notice='Preferência de imagens salva.';
                }
            }
            $selected=vpscloud_visual_mode($db);
            $labels=['dynamic'=>'Dinâmico — identificar pelo nome do plano','network'=>'Internet — roteador','fiber'=>'Fibra','rural'=>'Internet Rural','combo'=>'Combo','service'=>'Serviços','cloud'=>'Cloud','api'=>'Integrações','support'=>'Suporte','license'=>'Licenças','regulatory'=>'Regularização'];
            $options='';
            foreach($labels as $value=>$label) $options.='<option value="'.$value.'"'.($value===$selected?' selected':'').'>'.$label.'</option>';
            $block='<section id="vpscloud-image-settings" style="max-width:1100px;margin:32px auto;padding:24px;border:1px solid #dbe6ee;border-radius:12px;background:#f8fbff;font-family:Arial,sans-serif"><h3 style="margin:0 0 10px;font-size:17px">Imagens dos planos</h3><p style="color:#526579">Escolha uma imagem para todos os planos ou use o modo dinâmico para identificar pelo nome.</p><form method="post" action="hotsite_layout.hhvm"><input type="hidden" name="vpscloud_visual_save" value="1"><input type="hidden" name="csrf" value="'.htmlspecialchars($csrf,ENT_QUOTES,'UTF-8').'"><label for="vpscloud-visual">Tipo de imagem</label><select id="vpscloud-visual" name="visual_mode" style="display:block;width:100%;max-width:620px;margin:10px 0 16px;padding:11px;border:1px solid #cbd9e4;border-radius:5px">'.$options.'</select><button type="submit" style="border:0;background:#19a2ee;color:white;padding:11px 18px;border-radius:4px;cursor:pointer">Salvar imagens</button><p role="status">'.htmlspecialchars($notice,ENT_QUOTES,'UTF-8').'</p></form></section>';
            // Place below the native theme form, not inside its select or form.
            $selectPos=strpos($html,'layout-vpscloud-');
            $formEnd=stripos($html,'</form>',$selectPos);
            if($formEnd!==false)return substr_replace($html,$block,$formEnd+7,0);
            return $html;
        } catch(Throwable $e) {
            error_log('VPS CLOUD layout: '.$e->getMessage());
            return $html;
        }
    });
}

vpscloud_admin_layout_begin();
