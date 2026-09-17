<?php
if (!defined('ABSPATH')) exit;
require_once __DIR__.'/library.php';

final class Orthocal_Plugin {
    const CALENDAR = 'https://kalender.georg-kloster.ru/api/v1/calendar/';
    const BIBLE = 'https://bible-desktop.com/api/';
    const VERSION = '1.3.64';
    const LEGACY_IMAGE_HEIGHTS = ['small'=>28,'medium'=>44,'large'=>72];
    const TITLES = ['today'=>'Сегодня', 'upcoming'=>'Ближайшие праздники', 'month'=>'Календарь на месяц', 'year'=>'Календарь на год', 'day'=>'День календаря', 'readings'=>'Чтения дня', 'calendar'=>'Православный календарь','fasting'=>'Пост и трапеза','saints'=>'Памяти святых','feasts'=>'Праздники','memorial'=>'Поминальные дни','pascha'=>'Пасха','fasts'=>'Посты на год','date'=>'Дата по двум стилям','texts'=>'Богослужебные тексты','troparia'=>'Тропари','kontakia'=>'Кондаки','prayers'=>'Молитвы','magnifications'=>'Величания','horologion'=>'Часослов','akathists'=>'Акафисты','canons'=>'Каноны'];
    const TEXT_MODES=['texts','troparia','kontakia','prayers','magnifications','akathists','canons'];
    const SERVICE_MODES=['horologion'];
    private static $file;
    private static $memo = [];

    static function boot($file) {
        self::$file = $file;
        Orthocal_Media_Cache::boot();
        Orthocal_Admin::boot($file);
        add_action('init', [self::class, 'register']);
        add_action('wp_enqueue_scripts', function () {
            $post=get_post();
            if ($post && (str_contains($post->post_content,'[orthocal_') || str_contains($post->post_content,'wp:orthocal/'))) {
                wp_enqueue_style('orthocal'); wp_enqueue_script('orthocal');
            }
        });
        add_action('template_redirect', function () {
            $post=get_post();
            if ($post && (str_contains($post->post_content,'[orthocal_') || str_contains($post->post_content,'wp:orthocal/'))) {
                if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE',true);
                nocache_headers();
            }
        });
        add_action('wp_footer', function () {
            // Shortcodes in widgets/templates may be discovered after wp_head.
            if (wp_style_is('orthocal','enqueued') && !wp_style_is('orthocal','done')) wp_print_styles('orthocal');
        },5);
        add_action('admin_menu', function () { add_menu_page('Православный календарь', 'Православный календарь', 'manage_options', 'orthocal', [self::class, 'settings_page'], 'dashicons-calendar-alt', 58); });
        add_action('admin_enqueue_scripts', function ($hook) {
            if ($hook==='toplevel_page_orthocal') { wp_enqueue_style('orthocal'); wp_enqueue_script('orthocal'); wp_enqueue_style('orthocal-admin'); wp_enqueue_script('orthocal-admin'); }
        });
        add_action('admin_init', function () { register_setting('orthocal', 'orthocal_options', ['sanitize_callback'=>[self::class,'sanitize_options']]); });
        add_action('rest_api_init', function () {
            register_rest_route('orthocal/v1', '/font', ['methods'=>'GET','permission_callback'=>'__return_true','callback'=>function(){ $rate=self::throttle();if(is_wp_error($rate))return $rate;return new WP_REST_Response(['url'=>Orthocal_Media_Cache::url('/calendar-api-font.php')]); }]);
            register_rest_route('orthocal/v1', '/render', ['methods'=>'GET', 'permission_callback'=>'__return_true', 'callback'=>[self::class,'rest_render']]);
            register_rest_route('orthocal/v1', '/bible', ['methods'=>'GET', 'permission_callback'=>'__return_true', 'callback'=>[self::class,'rest_bible']]);
            register_rest_route('orthocal/v1', '/media', ['methods'=>'GET', 'permission_callback'=>'__return_true', 'callback'=>[self::class,'rest_media']]);
        });
    }

    static function image_height($value) {
        if (!is_scalar($value)) return 44;
        $value=trim((string)$value);
        if (isset(self::LEGACY_IMAGE_HEIGHTS[$value])) return self::LEGACY_IMAGE_HEIGHTS[$value];
        if (!preg_match('/^[1-9][0-9]*$/D',$value)) return 44;
        $height=(int)$value;
        return $height > 0 ? $height : 44;
    }
    static function options() {
        return wp_parse_args(get_option('orthocal_options', []), ['key'=>'','lang'=>'ru','profile'=>'typikon-strict','theme'=>'book','css_mode'=>'plugin','accent'=>'#9a352d','translation'=>'','oldstyle'=>'1','compact'=>'0','show_nav'=>'1','show_picker'=>'1','show_copy'=>'1','show_search'=>'0','show_section_titles'=>'1','show_font_size'=>'1','open'=>'inline','reading_open'=>'inline','day_page'=>'0','images'=>'1','image_size'=>'44','image_pack'=>'ornamental','icons'=>'0','icon_limit'=>'all','office'=>'horologion','heading'=>'1','branding'=>'Календарная мастерская','sections'=>'fasting,saints,readings,texts,icons','event_levels'=>'0,1,2,3,4','media_hours'=>'24','bible_hours'=>'24']);
    }
    static function sanitize_options($input) {
        $old = self::options(); $out = [];
        foreach (['lang'=>['ru','cu','de','uk','pl'], 'profile'=>['typikon-strict','parish'], 'theme'=>['book','modern','inherit'],'css_mode'=>['plugin','site'],'open'=>['inline','modal','new','page'],'reading_open'=>['inline','modal'],'image_pack'=>array_keys(Orthocal_Admin::packs())] as $name=>$allowed) {
            $out[$name] = in_array($input[$name] ?? '', $allowed, true) ? $input[$name] : $old[$name];
        }
        $out['image_size']=(string)self::image_height($input['image_size'] ?? $old['image_size']);
        $out['key'] = !empty($input['clear_key']) ? '' : (empty($input['key']) ? $old['key'] : sanitize_text_field($input['key']));
        $out['translation'] = array_key_exists('translation',$input) ? sanitize_text_field($input['translation']) : $old['translation'];
        $out['accent'] = array_key_exists('accent',$input) ? (sanitize_hex_color($input['accent']) ?: '#9a352d') : $old['accent'];
        foreach (['oldstyle','compact','show_nav','show_picker','show_copy','show_search','show_section_titles','show_font_size','images','icons','heading'] as $name) $out[$name] = array_key_exists($name,$input) ? (empty($input[$name]) ? '0' : '1') : $old[$name];
        $out['branding']=array_key_exists('branding',$input)?sanitize_text_field($input['branding']):$old['branding'];
        $out['day_page']=array_key_exists('day_page',$input)?(string)absint($input['day_page']):$old['day_page'];
        foreach(['media_hours'=>[6,12,24,168],'bible_hours'=>[0,1,6,24]] as $name=>$values) $out[$name]=(string)(in_array((int)($input[$name]??-1),$values,true)?(int)$input[$name]:(int)$old[$name]);
        if(array_key_exists('sections',$input)){$sections=is_array($input['sections'])?$input['sections']:explode(',',(string)$input['sections']);$out['sections']=implode(',',array_intersect(['fasting','saints','readings','texts','icons'],$sections));}
        else $out['sections']=$old['sections'];
        if(array_key_exists('event_levels',$input)){$levels=is_array($input['event_levels'])?$input['event_levels']:explode(',',(string)$input['event_levels']);$out['event_levels']=implode(',',array_intersect(['0','1','2','3','4'],$levels));}else $out['event_levels']=$old['event_levels'];
        // Invalidate the previous generation without scanning or deleting unrelated options.
        update_option('orthocal_cache_generation', wp_generate_uuid4(), false);
        return $out;
    }
    static function register() {
        $url = plugin_dir_url(self::$file);
        wp_register_style('orthocal', $url.'assets/calendar.css', [], self::VERSION);
        wp_register_script('orthocal', $url.'assets/calendar.js', [], self::VERSION, true);
        wp_register_script('orthocal-editor', $url.'assets/editor.js', ['wp-blocks','wp-element','wp-block-editor','wp-components','wp-server-side-render'], self::VERSION, true);
        wp_add_inline_script('orthocal-editor', 'window.OrthocalEditor='.wp_json_encode(['hasApiKey'=>self::key() !== '', 'settingsUrl'=>admin_url('admin.php?page=orthocal&tab=connection')]).';', 'before');
        wp_register_style('orthocal-admin',$url.'assets/admin.css',[],self::VERSION);
        wp_register_script('orthocal-admin',$url.'assets/admin.js',[],self::VERSION,true);
        foreach (self::TITLES as $mode=>$title) {
            add_shortcode('orthocal_'.$mode, function ($attrs) use ($mode) { return self::render(is_array($attrs) ? array_merge($attrs, ['mode'=>$mode]) : ['mode'=>$mode]); });
            register_block_type(dirname(self::$file).'/blocks/'.$mode, ['render_callback'=>function ($attrs) use ($mode) { return self::render(array_merge($attrs,['mode'=>$mode])); }]);
        }
    }
    static function key() { return defined('ORTHOCAL_API_KEY') ? ORTHOCAL_API_KEY : self::options()['key']; }
    static function date_valid($date) {
        return is_string($date) && preg_match('/^(19\d{2}|20\d{2}|21\d{2}|2200)-(\d{2})-(\d{2})$/D', $date, $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
    }
    static function config($attrs) {
        $o = self::options();
        $attrs = array_filter($attrs, static fn($value) => $value !== '');
        $a = shortcode_atts(['mode'=>'today','date'=>'','year'=>'','month'=>'','limit'=>'5','filter'=>'main','scope'=>'','tone'=>'','weekday'=>'','text_id'=>'','work'=>'','text_language'=>'','text_page'=>'1','translation'=>$o['translation'],'day_page'=>$o['day_page'],'accent'=>$o['accent'],'branding'=>$o['branding']]+array_intersect_key($o,array_flip(['lang','profile','theme','css_mode','compact','oldstyle','show_nav','show_picker','show_copy','show_search','show_section_titles','show_font_size','open','reading_open','images','image_size','image_pack','icons','icon_limit','office','heading','sections','event_levels'])), $attrs);
        $a['image_size']=(string)self::image_height($a['image_size']);
        if (!isset(self::TITLES[$a['mode']])) return new WP_Error('mode','Неизвестный блок.');
        if($a['mode']==='memorial')$a['filter']='memorial';
        if(!in_array($a['scope'],['','resurrection','weekday','common','annual'],true)||($a['tone']!==''&&!preg_match('/^[1-8]$/D',(string)$a['tone']))||($a['weekday']!==''&&!preg_match('/^[0-6]$/D',(string)$a['weekday']))||($a['text_id']!==''&&!preg_match('/^[a-z0-9-]{1,100}$/D',$a['text_id'])))return new WP_Error('texts','Неверные параметры библиотеки текстов.');
        $date = $a['date'] ?: wp_date('Y-m-d');
        if (!self::date_valid($date)) return new WP_Error('date','Дата должна быть в диапазоне 1900–2200, в формате ГГГГ-ММ-ДД.');
        $a['date'] = $date;
        $a['year'] = $a['year'] === '' ? (int)substr($date,0,4) : filter_var($a['year'], FILTER_VALIDATE_INT);
        $a['month'] = $a['month'] === '' ? (int)substr($date,5,2) : filter_var($a['month'], FILTER_VALIDATE_INT);
        $a['limit'] = filter_var($a['limit'], FILTER_VALIDATE_INT);
        if ($a['year'] < 1900 || $a['year'] > 2200 || $a['month'] < 1 || $a['month'] > 12 || $a['limit'] < 1 || $a['limit'] > 10) return new WP_Error('range','Неверный год, месяц или количество праздников.');
        foreach (['lang'=>['ru','cu','de','uk','pl'],'profile'=>['typikon-strict','parish'],'theme'=>['book','modern','inherit'],'css_mode'=>['plugin','site'],'filter'=>['main','twelve','great','memorial','all'],'open'=>['inline','modal','new','page'],'reading_open'=>['inline','modal'],'image_pack'=>array_keys(Orthocal_Admin::packs()),'icon_limit'=>['1','3','5','8','all'],'office'=>['horologion','first-hour','third-hour','sixth-hour','ninth-hour','matins','vespers','compline','typica']] as $name=>$allowed) if (!in_array($a[$name],$allowed,true)) return new WP_Error('option','Неверный параметр календаря.');
        if (!in_array($a['text_language'],array_merge([''],array_keys(Orthocal_Library::LANGUAGES)),true) || ($a['work']!==''&&!preg_match('/^[a-z0-9-]{1,150}$/D',$a['work']))) return new WP_Error('library','Неверный параметр библиотеки.');
        $a['text_page']=(string)max(1,min(10000,(int)$a['text_page']));
        $a['accent']=sanitize_hex_color((string)$a['accent']) ?: '#9a352d';
        $a['branding']=sanitize_text_field((string)$a['branding']);
        $a['translation']=sanitize_key((string)$a['translation']);
        $a['day_page']=(string)absint($a['day_page']);
        foreach (['compact','oldstyle','show_nav','show_picker','show_copy','show_search','show_section_titles','show_font_size','images','icons','heading'] as $name) $a[$name] = in_array((string)$a[$name], ['1','true'],true) ? '1' : '0';
        $a['event_levels']=implode(',',array_intersect(['0','1','2','3','4'],array_filter(explode(',',(string)$a['event_levels']),static fn($v)=>in_array($v,['0','1','2','3','4'],true))));
        if(!is_string($a['sections']) || array_diff(array_filter(explode(',',$a['sections'])),['fasting','saints','readings','texts','icons']))return new WP_Error('sections','Неизвестный раздел дня.');
        return $a;
    }
    static function request($service, $path, $query = []) {
        $calendar=in_array($service,['calendar','service'],true);
        $url = ($service === 'calendar' ? self::CALENDAR : ($service==='texts'?self::BIBLE.'liturgical/calendar-texts':($service==='service'?'https://kalender.georg-kloster.ru/api/v1/calendar/service':self::BIBLE))).$path;
        if ($query) $url = add_query_arg($query,$url);
        $cache = 'oc_'.md5(self::VERSION.'|'.$url.'|'.self::key().'|'.get_option('orthocal_cache_generation','0'));
        if (isset(self::$memo[$cache])) return self::$memo[$cache];
        $cached = get_transient($cache);
        if (is_array($cached)) return self::$memo[$cache] = $cached;
        $cooldown = get_transient('oc_cooldown_'.$service);
        if ($cooldown) return new WP_Error('busy','Сервис временно недоступен. Повторите позже.');
        $headers = ['Accept'=>'application/json'];
        if ($calendar) {
            // Public identifier only: it is deliberately not a secret or credential.
            $headers['X-Calendar-Client'] = 'orthocal-wordpress';
            if (self::key()) $headers['X-API-Key'] = self::key();
        }
        $response = wp_safe_remote_get($url, ['timeout'=>25,'redirection'=>0,'headers'=>$headers,'limit_response_size'=>12*1024*1024]);
        if (is_wp_error($response)) return new WP_Error('connection','Не удалось подключиться к '.($calendar ? 'календарю.' : 'BibleDesktop.'));
        $status = wp_remote_retrieve_response_code($response);
        if ($status !== 200) {
            if (in_array($status,[429,503],true)) set_transient('oc_cooldown_'.$service,1,min(3600,max(2,(int)wp_remote_retrieve_header($response,'retry-after'))));
            $messages = [401=>'API-ключ отсутствует или недействителен.',403=>'Доступ к API отключён или истёк.',429=>'Достигнут лимит запросов. Повторите позже.',503=>'API занят или обновляется. Повторите позже.'];
            return new WP_Error('upstream',$messages[$status] ?? 'Источник данных недоступен (HTTP '.$status.').');
        }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data)) return new WP_Error('schema','Источник вернул неверный формат данных.');
        if ($calendar) {
            $remaining = wp_remote_retrieve_header($response,'x-api-month-remaining');
            if ($remaining !== '') update_option('orthocal_quota', ['remaining'=>(int)$remaining,'checked'=>time()], false);
        }
        $ttl = $calendar ? min(300,max(0,(int)wp_remote_retrieve_header($response,'x-calendar-application-cache-ttl'))) : (int)self::options()['bible_hours']*3600;
        if($service==='bible') {
            $permission=wp_remote_retrieve_header($response,'x-bible-application-cache-ttl');
            if($permission!=='')$ttl=min($ttl,max(0,(int)$permission));
            elseif(stripos(wp_remote_retrieve_header($response,'cache-control'),'no-store')!==false)$ttl=0;
        }
        if ($service==='bible' && str_starts_with($path,'liturgical/')) $ttl=min($ttl,300);
        if ($ttl) set_transient($cache,$data,$ttl);
        return self::$memo[$cache] = $data;
    }
    static function data($a) {
        $q = ['lang'=>$a['lang'],'profile'=>$a['profile']]; $mode = $a['mode'];
        if(in_array($mode,['troparia','kontakia'],true)&&Orthocal_Library::language($a)==='ru')return Orthocal_Library::data($a);
        if(in_array($mode,['horologion','akathists','canons','prayers'],true)) return Orthocal_Library::data($a);
        if(in_array($mode,self::TEXT_MODES,true)) {
            $types=['troparia'=>'troparion','kontakia'=>'kontakion','prayers'=>'prayer','magnifications'=>'magnification'];$query=['per_page'=>100,'page'=>$a['text_page']];
            if(isset($types[$mode]))$query['type']=$types[$mode];
            foreach(['scope','tone','weekday'] as $key)if($a[$key]!=='')$query[$key]=$a[$key];
            if($a['text_id']!=='')$query['id']=$a['text_id'];
            $language=Orthocal_Library::language($a);
            if(($a['text_language']??'')==='') {
                $counts=[];
                foreach(array_keys(Orthocal_Library::LANGUAGES) as $candidate) {
                    $probe=self::request('texts','',array_merge($query,['language'=>$candidate,'page'=>1,'per_page'=>1]));
                    if(!is_wp_error($probe))$counts[$candidate]=(int)($probe['total']??$probe['count']??0);
                }
                $language=Orthocal_Library::preferred_language($a,$counts);
            }
            $data=self::request('texts','',array_merge($query,['language'=>$language]));
            if(is_array($data))$data['language']=$language;
            return $data;
        }
        if (in_array($mode,['today','day','readings','fasting','saints','date'],true)) {
            return self::request('calendar','day',$q+['date'=>$a['date']]);
        }
        if (in_array($mode,['upcoming','feasts','memorial'],true)) return self::request('calendar','upcoming',$q+['date'=>$a['date'],'limit'=>$a['limit'],'filter'=>$a['filter']]);
        if($mode==='pascha')return self::request('calendar','pascha',$q+['year'=>$a['year']]);
        $q += ['year'=>$a['year'],'view'=>'summary'];
        if (in_array($mode,['month','calendar'],true)) $q['month'] = $a['month'];
        return self::request('calendar',in_array($mode,['month','calendar'],true)?'month':'year',$q);
    }
    static function error_html($message) { return '<p class="oc-message" role="status">'.esc_html($message).'</p>'; }
    static function reading_controls($a) {
        if ($a['reading_open']!=='inline') return '';
        return '<div class="oc-reading-controls"><label class="oc-reading-translation">'.self::ui('Перевод',$a['lang']).' <select data-oc-translation><option value="">'.self::ui('Загрузить переводы…',$a['lang']).'</option></select></label>'.($a['show_font_size']==='1'?('<label class="oc-reading-font">'.self::ui('Размер текста',$a['lang']).' <input data-oc-font type="range" min="16" max="30" value="19"></label>'):'').'<p class="oc-muted">'.self::ui('Текст Апостола и Евангелия открывается по выбранному переводу. Псалтирь приведена по богослужебному распределению дня.',$a['lang']).'</p></div>';
    }
    static function render($attrs, $ajax = false) {
        $a = self::config($attrs);
        if (is_wp_error($a)) return self::error_html($a->get_error_message());
        $selected = false;
        if (!$ajax && !empty($_GET['orthocal_date']) && is_string($_GET['orthocal_date']) && self::date_valid($_GET['orthocal_date'])) {
            $selected = true;
            $a['date'] = $_GET['orthocal_date']; $a['year'] = (int)substr($a['date'],0,4); $a['month'] = (int)substr($a['date'],5,2);
            if (in_array($a['mode'],['today','day'],true)) $a['mode'] = 'day';
        }
        wp_enqueue_style('orthocal'); wp_enqueue_script('orthocal');
        $data = self::data($a);
        if(is_array($data)&&in_array($a['mode'],self::TEXT_MODES,true)&&isset($data['language']))$a['text_language']=$data['language'];
        $page=(int)$a['day_page'];$pageUrl=$page && get_post_status($page)==='publish'?get_permalink($page):'';
        $has_liturgical_font=$a['lang']==='cu'||in_array($a['mode'],array_merge(self::TEXT_MODES,self::SERVICE_MODES),true);
        $config = $a + ['endpoint'=>rest_url('orthocal/v1/'), 'liveDate'=>empty($attrs['date']) && empty($_GET['orthocal_date']), 'pageUrl'=>$pageUrl,'ui'=>self::ui_catalog($a['lang']),'fontUrl'=>$has_liturgical_font?Orthocal_Media_Cache::url('/calendar-api-font.php'):''];
        $html = '<section class="orthocal oc-theme-'.esc_attr($a['theme']).($a['css_mode']==='site'?' oc-site-css':'').($a['compact']==='1'?' oc-compact':'').'" style="--oc-accent:'.esc_attr($a['accent']).';--oc-image-height:'.(int)$a['image_size'].'px" data-oc-language="'.esc_attr($a['lang']).'" data-orthocal="'.esc_attr(wp_json_encode($config)).'" aria-label="'.esc_attr(self::ui(self::TITLES[$a['mode']],$a['lang'])).'">';
        if($a['heading']==='1')$html .= '<div class="oc-heading">'.($a['branding']!==''?'<span class="oc-eyebrow">'.esc_html(self::ui($a['branding'],$a['lang'])).'</span>':'').(in_array($a['mode'],['today','day'],true)?'':'<h2>'.esc_html(self::ui(self::TITLES[$a['mode']],$a['lang'])).'</h2>').'</div>';
        if (is_wp_error($data)) $html .= self::error_html($data->get_error_message()).('<button type="button" data-oc-retry>'.self::ui('Повторить',$a['lang']).'</button>');
        elseif(!empty($data['library']))$html.=Orthocal_Library::render($data,$a);
        elseif(in_array($a['mode'],self::TEXT_MODES,true)&&isset($data['texts']))$html.=self::texts($data,$a);
        elseif (isset($data['day']['events']) && is_array($data['day']['events'])) $html .= self::day($data['day'],$a);
        elseif ($a['mode']==='pascha' && isset($data['pascha'])) $html.='<p class="oc-date">'.self::day_link($data['pascha'],self::date_label($data['pascha'],$a['lang'])).'</p><div class="oc-detail"></div>';
        elseif ($a['mode']==='fasts' && isset($data['fastingPeriods'])) $html.='<ul class="oc-periods">'.self::periods($data['fastingPeriods']).'</ul>';
        elseif (in_array($a['mode'],['upcoming','feasts','memorial'],true) && isset($data['items'])) {
            $html .= '<div class="oc-upcoming">';
            foreach ($data['items'] as $item) {
                $days = (int)(new DateTimeImmutable($a['date']))->diff(new DateTimeImmutable($item['date']))->format('%a');
                $html .= '<article><span class="oc-eyebrow">'.esc_html(self::date_label($item['date'],$a['lang'])).' · '.($days ? 'через '.$days.' дн.' : 'сегодня').'</span>'.self::day_link($item['date'],$item['event']['title']).'</article>';
            }
            $html .= empty($data['items']) ? '<p>В указанном диапазоне событий нет.</p>' : '';
            $html .= '</div><div class="oc-detail">'.($selected ? self::render(array_merge($a,['mode'=>'day']),true) : '').'</div>';
        } elseif (isset($data['days']) && is_array($data['days'])) {
            $html .= self::navigation($a);
            if ($a['mode']==='year') {
                $html .= ('<p class="oc-pascha">'.self::ui('Пасха',$a['lang']).' · ').esc_html(self::date_label($data['pascha'],$a['lang'])).'</p><div class="oc-year">';
                for ($m=1;$m<=12;$m++) $html .= self::month(array_values(array_filter($data['days'],static fn($day)=>(int)substr($day['date'],5,2)===$m)), $a, $m);
                $html .= '</div>';
                if (!empty($data['fastingPeriods'])) $html .= '<details><summary>Постные периоды</summary><ul>'.self::periods($data['fastingPeriods']).'</ul></details>';
            } else $html .= self::month($data['days'],$a,$a['month']);
            $html .= ('<details class="oc-legend"><summary>Как читать календарь</summary><p>✦ — великий праздник. Красным выделены воскресенья и великие праздники. Маленькое число — дата '.self::ui('по старому стилю',$a['lang']).'. Выберите день, чтобы увидеть все памяти, знаки Типикона и правило поста.</p></details><div class="oc-detail">').($selected || $a['mode']==='calendar' ? self::render(array_merge($a,['mode'=>'day']),true) : '').'</div>';
        } else $html .= self::error_html('API требует обновления: ожидаемые поля отсутствуют.');
        return $html.'<p class="oc-status" role="status" aria-live="polite"></p></section>';
    }
    static function ui_catalog($lang) {
        static $catalog=null;
        if ($catalog===null) $catalog=json_decode(file_get_contents(__DIR__.'/../assets/i18n.json'),true);
        return $catalog[$lang]??[];
    }
    static function ui($text,$lang) { return self::ui_catalog($lang)[$text]??$text; }
    static function date_label($date,$lang='ru') {
        if ($lang==='de') return (int)substr($date,8,2).'. '.['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'][(int)substr($date,5,2)-1].' '.substr($date,0,4);
        $months=['января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];
        return (int)substr($date,8,2).' '.$months[(int)substr($date,5,2)-1].' '.substr($date,0,4);
    }
    static function day_link($date,$label) {
        return '<a href="'.esc_url(self::date_url($date)).'" data-oc-date="'.esc_attr($date).'">'.esc_html($label).'</a>';
    }
    static function date_url($date) {
        $page=(int)self::options()['day_page'];$base=$page && get_post_status($page)==='publish'?get_permalink($page):false;
        return add_query_arg('orthocal_date',$date,$base);
    }
    static function navigation($a) {
        $year = $a['mode']==='year'; $date = new DateTimeImmutable(sprintf('%04d-%02d-01',$a['year'],$a['month']));
        $html = ('<nav class="oc-nav" aria-label="'.self::ui('Выбор периода',$a['lang']).'">');
        foreach ([-1,1] as $step) {
            $target = $date->modify(($step<0?'-1':'+1').($year?' year':' month'));
            $disabled = (int)$target->format('Y')<1900 || (int)$target->format('Y')>2200;
            $html .= '<button type="button" data-oc-period="'.$target->format('Y-m-d').'" '.($disabled?'disabled':'').'>'.($step<0?(self::ui('← Назад',$a['lang'])):(self::ui('Вперёд →',$a['lang']))).'</button>';
        }
        $html .= ('<label>'.self::ui('Год',$a['lang']).' <input data-oc-year type="number" min="1900" max="2200" value="').$a['year'].('"></label><button type="button" data-oc-now>'.self::ui('Сегодня',$a['lang']).'</button></nav>');
        return $html;
    }
    static function month($days,$a,$month) {
        $months=['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'];
        $months=$a['lang']==='de'?['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember']:$months;
        $html = '<div class="oc-month"><h3>'.esc_html($months[$month-1].' '.$a['year']).'</h3><div class="oc-grid">';
        foreach (($a['lang']==='de'?['Mo','Di','Mi','Do','Fr','Sa','So']:['Пн','Вт','Ср','Чт','Пт','Сб','Вс']) as $name) $html .= '<span class="oc-weekday">'.$name.'</span>';
        if ($days) for ($i=0;$i<(($days[0]['weekday']+6)%7);$i++) $html .= '<span aria-hidden="true"></span>';
        foreach ($days as $day) {
            $events = $day['events'] ?? []; $feasts = array_values(array_filter($events,static fn($e)=>$e['category']==='commemoration' && $e['typeCode']>=0 && $e['typeCode']<=2));
            $red = $day['weekday']===0 || count($feasts)>0; $today = $day['date']===wp_date('Y-m-d');
            $label = self::date_label($day['date'],$a['lang']).'. '.($day['foodLabel'] ?? '').'. '.implode('. ',array_column($feasts,'title'));
            $html .= '<a class="oc-cell'.($red?' oc-red':'').($today?' oc-today':'').'" href="'.esc_url(self::date_url($day['date'])).'" data-oc-date="'.esc_attr($day['date']).'" aria-label="'.esc_attr($label).'" title="'.esc_attr($label).'"'.($today?' aria-current="date"':'').'>';
            $html .= '<b>'.(int)substr($day['date'],8,2).'</b>'.($a['oldstyle']==='1'?'<small>'.(int)substr($day['oldStyleDate'],8,2).'</small>':'');
            if ($feasts) $html .= '<span class="oc-star" aria-hidden="true">✦</span>';
            if ($a['mode']!=='year') $html .= '<span class="oc-cell-title">'.esc_html($feasts[0]['title'] ?? $events[0]['title'] ?? '').'</span><span class="oc-food">'.esc_html($day['foodLabel'] ?? '').'</span>';
            $html .= '</a>';
        }
        return $html.'</div></div>';
    }
    static function periods($periods) {
        $html='';
        foreach ($periods as $period) {
            if (!isset($period['label'],$period['start']['year'],$period['finish']['year'])) continue;
            $format = static fn($d) => sprintf('%04d-%02d-%02d',$d['year'],$d['month'],$d['day']);
            $html.='<li>'.esc_html($period['label'].' · '.self::date_label($format($period['start'])).' — '.self::date_label($format($period['finish']))).'</li>';
        }
        return $html;
    }
    static function event_level($code): string {
        $code=(int)$code;
        if($code<=1)return '0';
        if($code===2)return '1';
        if($code>=3&&$code<=5)return '2';
        if($code>=6&&$code<=8)return '3';
        return '4';
    }
    static function event_allowed($event,$levels): bool {
        if (!is_array($event) || ($event['category']??'')!=='commemoration') return false;
        $levels=array_values(array_filter(array_map('trim',explode(',',(string)$levels)),static fn($level)=>$level!==''));
        // An empty selection is treated as “all”, so an incomplete shortcode
        // can never make the whole holidays and memorials section disappear.
        return !$levels || in_array(self::event_level($event['typeCode']??-1),$levels,true);
    }
    static function icon_dates($item): array {
        $labels=[];
        foreach((array)($item['dates']??[]) as $date) {
            $label=is_array($date)?($date['label']??''):$date;
            if(is_string($label)&&trim($label)!=='')$labels[]=trim($label);
        }
        return array_values(array_unique($labels));
    }
    static function icon_dates_label($item): string {
        $dates=array_map(static function($label) {
            $label=preg_replace('/\s+[-—]\s+.*/u','',$label)??$label;
            return preg_replace('/\s*\(переходящая\)/iu',' (пер.)',$label)??$label;
        },self::icon_dates($item));
        return implode('; ',$dates);
    }
    static function icon_image($item,$lazy=true): string {
        if(!is_array($item))return '';
        $source=!empty($item['localUrl'])?(string)$item['localUrl']:(string)($item['images'][0]??'');
        if($source==='')return '';
        $attr=!empty($item['localUrl'])?' src="'.esc_url($source).'"':' data-oc-icon-cover="'.esc_url($source).'"';
        return '<img'.$attr.' alt="'.esc_attr($item['alt']??'Икона').'"'.($lazy?' loading="lazy"':'').'>';
    }
    static function icon_image_count($item): string {
        $count=count(array_filter((array)($item['images']??[]),static fn($image)=>is_string($image)&&$image!==''));
        return '<span class="oc-icon-image-count" aria-hidden="true">'.$count.'</span>';
    }
    static function icon_items($day): array {
        $items=[];
        foreach (($day['icons']??[]) as $icon) {
            if(!is_array($icon))continue;
            $sources=[];
            foreach(($icon['images']??[]) as $image)if(is_array($image)&&is_string($image['url']??null))$sources[]=$image['url'];
            if(!empty($icon['imageUrl']))$sources[]=$icon['imageUrl'];
            $images=array_values(array_filter(array_values(array_unique($sources)),static fn($source)=>Orthocal_Media_Cache::source($source)!==false));
            $images=array_values(array_unique($images));if(!$images)continue;
            // Only the visible cover is fetched while rendering the day. Every
            // other image stays as a validated source for the modal's lazy loader.
            // Rendering a calendar must never synchronously fetch all icon
            // covers. The client lazily asks for an uncached cover only when
            // it becomes visible or is opened in the gallery.
            $items[]=['localUrl'=>Orthocal_Media_Cache::cached_url($images[0]),'images'=>$images,'alt'=>$icon['title']??'Икона','description'=>$icon['description']??$icon['caption']??'','attribution'=>$icon['attribution']??'','kind'=>$icon['kind']??'','dates'=>$icon['dates']??[],'calendarRank'=>$icon['calendarRank']??null];
        }
        foreach((array)apply_filters('orthocal_day_icons',[],$day) as $item) {
            if(!is_array($item))continue;
            $upload=wp_upload_dir();
            $images=array_values(array_unique(array_filter(array_merge((array)($item['images']??[]),[$item['localUrl']??'']),static function($url)use($upload){
                if(!is_string($url)||!str_starts_with($url,$upload['baseurl'].'/orthocal-cache/'))return false;
                $name=substr($url,strlen($upload['baseurl'].'/orthocal-cache/'));
                return Orthocal_Media_Cache::file_valid($name)&&is_file($upload['basedir'].'/orthocal-cache/'.$name);
            })));
            if(!$images)continue;
            $items[]=['localUrl'=>$images[0],'images'=>$images,'alt'=>$item['alt']??'Икона','description'=>$item['description']??$item['caption']??'','attribution'=>$item['attribution']??'','kind'=>$item['kind']??'','dates'=>$item['dates']??[],'calendarRank'=>$item['calendarRank']??null];
        }
        // The calendar API has already matched each catalogue entry to the
        // resolved MemoryDays event and returned the authoritative order.
        // Never re-rank it here by a partial title comparison.
        return $items;
    }
    static function day($day,$a) {
        $only=['fasting'=>'fasting','saints'=>'saints','readings'=>'readings','date'=>''];
        $sections=isset($only[$a['mode']])?[$only[$a['mode']]]:explode(',',$a['sections']);
        // The configured icon limit affects only cards rendered in the block.
        // The hidden gallery data always contains every icon for the selected day.
        $allIconItems = in_array('icons',$sections,true) ? self::icon_items($day) : [];
        $hero = $allIconItems ? self::hero_icon($allIconItems[0],0,$a['lang']) : '';
        $gallery=$allIconItems?'<div hidden data-oc-day-icon-gallery="'.self::icon_gallery_data($allIconItems).'"></div>':'';
        $html = $gallery.$hero.'<div class="oc-day"><div class="oc-date-row"><p class="oc-date">'.esc_html(self::date_label($day['date'],$a['lang'])).'</p>';
        if (in_array($a['mode'],['today','day'],true) && $a['show_picker']==='1') $html .= ('<label class="oc-date-picker"><span class="screen-reader-text">'.self::ui('Выбрать дату',$a['lang']).'</span><input type="date" aria-label="'.self::ui('Выбрать дату',$a['lang']).'" title="'.self::ui('Выбрать дату',$a['lang']).'" data-oc-picker min="1900-01-01" max="2200-12-31" value="').esc_attr($day['date']).'"></label>';
        $html .= '</div>';
        if ($a['oldstyle']==='1') $html .= '<p class="oc-muted">'.esc_html($day['oldStyleDate']).(' '.self::ui('по старому стилю',$a['lang']).'</p>');
        $meta=[];if(!empty($day['weekdayName']))$meta[]=mb_strtolower((string)$day['weekdayName']);if(!empty($day['weekAfterPentecost']))$meta[]=(int)$day['weekAfterPentecost'].(self::ui('-я седмица по Пятидесятнице',$a['lang']));if(!empty($day['tone']))$meta[]=(self::ui('глас ',$a['lang'])).(int)$day['tone'];if($meta)$html.='<p class="oc-day-meta">'.esc_html(implode(' · ',$meta)).'</p>';
        if(in_array($a['mode'],['today','day'],true)) {
            $date=new DateTimeImmutable($day['date']);$html.=$a['show_nav']==='1'?('<nav class="oc-day-nav" aria-label="'.self::ui('Выбор дня',$a['lang']).'">'):'';
            if($a['show_nav']==='1') foreach([-1=>(self::ui('← Вчера',$a['lang'])),1=>(self::ui('Завтра →',$a['lang']))] as $step=>$label){$target=$date->modify(($step<0?'-1':'+1').' day')->format('Y-m-d');if(self::date_valid($target))$html.='<button type="button" data-oc-day="'.$target.'">'.($a['date']===wp_date('Y-m-d')?$label:($step<0?(self::ui('← Предыдущий день',$a['lang'])):(self::ui('Следующий день →',$a['lang'])))).'</button>';}
            $html.=$a['show_nav']==='1'?'</nav>':'';
        }
        if(in_array('saints',$sections,true)) {
            $events=array_values(array_filter($day['events'],static fn($event)=>self::event_allowed($event,$a['event_levels'])));
            usort($events,static function($left,$right){$rank=static function($event){$title=(string)($event['title']??'');$monk=preg_match('/^(?:прп\.|преподобн)/iu',$title)?0:1;return [$monk,(int)($event['typeCode']??99),$title];};return $rank($left)<=>$rank($right);});
            $html.='<section class="oc-saints-section">'.($a['show_section_titles']==='1'?('<h3>'.self::ui('Праздники и памяти',$a['lang']).'</h3>'):'').($a['show_search']==='1'?('<label class="oc-search">'.self::ui('Найти в памятях дня',$a['lang']).' <input type="search" data-oc-event-search placeholder="'.self::ui('Имя или название',$a['lang']).'"></label>'):'').'<ul class="oc-events">';
            foreach ($events as $event) {
                $mark=$event['typikonMark'] ?? null;
                $html .= '<li'.(($event['typeCode']>=0 && $event['typeCode']<=2)?' class="oc-red"':'').'>';
                $local=$a['images']==='1'&&$mark?Orthocal_Media_Cache::url($mark['svgSource']??''):'';
                // A saint's rank is supplied solely by the calendar API. The former
                // decorative cross for every venerable saint resembled the red
                // polyeleos sign and falsely elevated ordinary commemorations.
                if ($local) $html .= '<img width="20" height="20" src="'.esc_url($local).'" alt="'.esc_attr($mark['label'] ?? 'Знак Типикона').'" title="'.esc_attr($mark['label']??'Знак Типикона').'"> ';
                $html .= esc_html($event['title']).'</li>';
            }
            $html .= ('</ul><p data-oc-no-events hidden>'.self::ui('Совпадений нет.',$a['lang']).'</p></section>');
        }
        if (in_array('fasting',$sections,true)) {
            $foodImage='';$markers=$day['foodMarkers']??[];$selected=array_values(array_filter($markers,static fn($marker)=>$marker['packId']===$a['image_pack']));
            $foodRule=$day['fasting']['foodRule']['id']??'';
            $foodSource=$foodRule==='no-fast'?'':($selected[0]['source']??$markers[0]['source']??'');
            $local=$a['images']==='1'?Orthocal_Media_Cache::url($foodSource):'';
            if ($local) $foodImage='<img src="'.esc_url($local).'" height="'.(int)$a['image_size'].'" alt="">';
            $profileLabel=$a['profile']==='parish'?(self::ui('Приходской',$a['lang'])):(self::ui('Монастырский',$a['lang']));
            $html .= '<section class="oc-fasting-section">'.($a['show_section_titles']==='1'?('<h3>'.self::ui('Пост и трапеза',$a['lang']).'</h3>'):'').'<p class="oc-fast">'.$foodImage.esc_html($day['foodLabel'] ?? '').'<sup class="oc-profile-mark" aria-label="'.$profileLabel.'">*</sup></p></section>';
        }
        if(in_array('icons',$sections,true))$html.=self::icons_slot($allIconItems,$a['icon_limit'],$a['lang']);
        $psalter=array_values(array_filter($day['events'],static fn($e)=>(int)($e['typeCode']??0)===302));
        $readings=array_values(array_filter($day['events'],static fn($e)=>$e['category']==='scripture-reading'&&(int)($e['typeCode']??0)!==302));
        if (($readings || $psalter) && in_array('readings',$sections,true)) {
            $html .= '<div class="oc-readings">'.($a['show_section_titles']==='1'?('<h3>'.self::ui('Библейские чтения',$a['lang']).'</h3>'):'').self::reading_controls($a);
            foreach ($readings as $event) $html .= '<details data-oc-reading="'.esc_attr(wp_json_encode($event['reading'] ?? null)).'"><summary>'.esc_html($event['title']).('</summary><div class="oc-reading-body"><button type="button" data-oc-copy-reading>'.self::ui('Скопировать текст',$a['lang']).'</button><div class="oc-verses" aria-live="polite"></div></div></details>');
            foreach ($psalter as $event) {
                $parts=array_values(array_filter(array_map('trim',explode(';',(string)($event['title']??'')))));
                if (!$parts) continue;
                $vespers=count($parts)>=3?array_pop($parts):null;
                $html.=('<section class="oc-psalter"><h4>'.self::ui('Псалтирь',$a['lang']).'</h4><p><strong>'.self::ui('На утрене:',$a['lang']).'</strong> ').esc_html(implode('; ',$parts)).'</p>';
                if ($vespers!==null) $html.=('<p><strong>'.self::ui('На вечерне:',$a['lang']).'</strong> ').esc_html($vespers).'</p>';
                $html.='</section>';
            }
            $html .= '</div>';
        } elseif ($a['mode']==='readings') $html .= ('<p>'.self::ui('В источнике нет чтений для этой даты.',$a['lang']).'</p>');
        if(in_array('texts',$sections,true)) {
            $html.='<section class="oc-texts-section">'.($a['show_section_titles']==='1'?('<h3>'.self::ui('Богослужебные тексты',$a['lang']).'</h3>'):'');
            $html.=('<p class="oc-muted">'.self::ui('Откройте справочник и выберите нужный текст.',$a['lang']).'</p><div class="oc-text-buttons">');foreach(['troparia'=>(self::ui('Тропари',$a['lang'])),'kontakia'=>(self::ui('Кондаки',$a['lang'])),'prayers'=>(self::ui('Молитвы',$a['lang'])),'magnifications'=>(self::ui('Величания',$a['lang'])),'horologion'=>(self::ui('Часослов',$a['lang'])),'akathists'=>self::ui('Акафисты',$a['lang']),'canons'=>self::ui('Каноны',$a['lang'])] as $mode=>$label)$html.='<button type="button" data-oc-library="'.$mode.'">'.$label.'</button>';$html.='</div></section>';
        }
        $profileLabel=$a['profile']==='parish'?(self::ui('Приходской',$a['lang'])):(self::ui('Монастырский',$a['lang']));
        return $html.($a['show_copy']==='1'?'<p class="oc-permalink">'.self::day_link($day['date'],(self::ui('Ссылка на этот день',$a['lang']))).(' <button type="button" data-oc-copy-link>'.self::ui('Скопировать ссылку',$a['lang']).'</button> <small class="oc-profile-note">* ').$profileLabel.(self::ui(' профиль',$a['lang']).'</small></p>'):'<p class="oc-profile-note">* '.$profileLabel.(self::ui(' профиль',$a['lang']).'</p>')).'</div>';
    }
    static function icon_gallery_data($items): string {
        $gallery=[];
        foreach($items as $item) {
            if(!is_array($item)||empty($item['images']))continue;
            $gallery[]=['title'=>(string)($item['alt']??'Икона'),'description'=>(string)($item['description']??$item['caption']??''),'dates'=>self::icon_dates($item),'images'=>array_values((array)$item['images'])];
        }
        return esc_attr(wp_json_encode($gallery,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    }
    static function icon_data_attributes($item,$index): string {
        $description=$item['description']??$item['caption']??$item['alt']??'';
        return ' data-oc-icon data-oc-icon-index="'.(int)$index.'" data-oc-icon-title="'.esc_attr($item['alt']??'Икона').'" data-oc-icon-description="'.esc_attr($description).'"';
    }
    static function icons_slot($items,$limit='all',$lang='ru') {
        $html='';$upload=wp_upload_dir();
        $items=is_array($items)?$items:[];
        $entries=[];
        foreach(array_slice($items,1,null,true) as $index=>$item)$entries[]=['item'=>$item,'index'=>$index];
        if($limit!=='all')$entries=array_slice($entries,0,max(0,(int)$limit-1));
        foreach($entries as $entry) {
            $item=$entry['item'];$index=$entry['index'];
            if(!is_array($item)||empty($item['alt']))continue;
            if(!empty($item['localUrl'])&&str_starts_with($item['localUrl'],$upload['baseurl'].'/orthocal-cache/')) {
                $name=substr($item['localUrl'],strlen($upload['baseurl'].'/orthocal-cache/'));
                if(!Orthocal_Media_Cache::file_valid($name)||!is_file($upload['basedir'].'/orthocal-cache/'.$name))$item['localUrl']='';
            }
            $source=!empty($item['localUrl'])?$item['localUrl']:($item['images'][0]??'');if(!is_string($source)||$source==='')continue;
            $html.='<figure><a class="oc-icon-link" href="'.esc_url($source).'"'.self::icon_data_attributes($item,$index).' aria-label="Открыть икону: '.esc_attr($item['alt']).'">'.self::icon_image($item).self::icon_image_count($item).'</a><figcaption><span>'.esc_html($item['alt']).(!empty($item['attribution'])?' · '.esc_html($item['attribution']):'').'</span><span class="oc-icon-dates">'.esc_html(self::icon_dates_label($item)).'</span></figcaption></figure>';
        }
        return $html?'<div class="oc-icons">'.$html.'</div>':'';
    }
    static function hero_icon($item,$index=0,$lang='ru') {
        if(!is_array($item))return '';
        $source=!empty($item['localUrl'])?$item['localUrl']:($item['images'][0]??'');if(!is_string($source)||$source==='')return '';
        return '<div class="oc-hero-icon"><a class="oc-icon-link" href="'.esc_url($source).'"'.self::icon_data_attributes($item,$index).' aria-label="Открыть икону: '.esc_attr($item['alt']??'Икона').'">'.self::icon_image($item).self::icon_image_count($item).'</a><p class="oc-icon-dates">'.esc_html(self::icon_dates_label($item)).'</p></div>';
    }
    static function texts($data,$a) {
        $html='<div class="oc-text-filters">'.Orthocal_Library::language_control($a);
        $scopes=$data['filters']['scopes']??array_values(array_unique(array_column($data['texts'],'scope')));
        $tones=$data['filters']['tones']??array_values(array_unique(array_filter(array_column($data['texts'],'tone'))));
        $labels=[''=>'Все разделы','resurrection'=>'Воскресные','weekday'=>'Дни седмицы','common'=>'Общие и справочные','annual'=>'Праздники и святые'];
        if(count($scopes)>1 || $a['scope']!=='') {
            $html.='<label>'.self::ui('Раздел',$a['lang']).' <select data-oc-text-filter="scope">';
            foreach(array_unique(array_merge([''],$scopes,[$a['scope']])) as $value)$html.='<option value="'.esc_attr($value).'" '.selected($a['scope'],$value,false).'>'.esc_html(self::ui($labels[$value]??$value,$a['lang'])).'</option>';
            $html.='</select></label>';
        }
        if($tones || $a['tone']!=='') {
            $html.='<label>'.self::ui('Глас',$a['lang']).' <select data-oc-text-filter="tone"><option value="">'.self::ui('Все гласы',$a['lang']).'</option>';
            foreach(array_unique(array_merge($tones,$a['tone']!==''?[(int)$a['tone']]:[])) as $tone)$html.='<option value="'.(int)$tone.'" '.selected((string)$a['tone'],(string)$tone,false).'>'.(int)$tone.'</option>';
            $html.='</select></label>';
        }
        $html.='</div>';
        foreach($data['texts'] as $text) {
            $html.='<details class="oc-liturgical-text"><summary>'.esc_html($text['title']).('</summary><div class="oc-reading-body"><button type="button" data-oc-copy-reading>'.self::ui('Скопировать текст',$a['lang']).'</button><div class="oc-verses oc-library-content" '.Orthocal_Library::text_attributes($a,$text).'>').nl2br(esc_html($text['text'])).'</div><p class="oc-text-source">';
            foreach($text['sources']??[] as $source)$html.='<a href="'.esc_url($source['url']).'" target="_blank" rel="noopener noreferrer">'.esc_html($source['title']).'</a> ';
            $html.='</p></div></details>';
        }
        if(($data['total']??0)>100) {
            $html.='<nav class="oc-nav">';
            if((int)$a['text_page']>1)$html.='<button type="button" data-oc-text-page="'.((int)$a['text_page']-1).'">←</button>';
            $html.='<span>'.(int)$a['text_page'].' / '.(int)ceil($data['total']/100).'</span>';
            if((int)$a['text_page']*100<$data['total'])$html.='<button type="button" data-oc-text-page="'.((int)$a['text_page']+1).'">→</button>';
            $html.='</nav>';
        }
        return $html.Orthocal_Library::sources($a).(empty($data['texts'])?('<p>'.self::ui('В этой части библиотеки пока нет текстов с выбранными параметрами.',$a['lang']).'</p>'):'');
    }
    static function throttle() {
        // Bound public proxy traffic. No forwarded headers or arbitrary upstream URL accepted.
        foreach (['global'=>300, hash('sha256',($_SERVER['REMOTE_ADDR'] ?? '').wp_salt())=>40] as $id=>$max) {
            $key='oc_rate_'.md5($id.gmdate('YmdHi')); $count=(int)get_transient($key);
            if ($count >= $max) return new WP_Error('rate_limit','Слишком много запросов. Повторите через минуту.',['status'=>429]);
            set_transient($key,$count+1,70);
        }
        return true;
    }
    static function media_throttle() {
        // Cache hits do not call this method. A cold cache can legitimately
        // contain a complete day with many images, so its allowance must not
        // be confused with the small public API proxy allowance above.
        foreach (['global'=>600, hash('sha256',($_SERVER['REMOTE_ADDR'] ?? '').wp_salt())=>180] as $id=>$max) {
            $key='oc_media_rate_'.md5($id.gmdate('YmdHi')); $count=(int)get_transient($key);
            if ($count >= $max) return new WP_Error('rate_limit','Слишком много запросов. Повторите через минуту.',['status'=>429]);
            set_transient($key,$count+1,70);
        }
        return true;
    }
    static function rest_render($request) {
        $rate=self::throttle(); if (is_wp_error($rate)) return $rate;
        $attrs=$request->get_query_params();
        foreach ($attrs as $value) if (!is_scalar($value)) return new WP_Error('invalid','Неверные параметры.',['status'=>400]);
        $a=self::config($attrs); if (is_wp_error($a)) return new WP_Error('invalid',$a->get_error_message(),['status'=>400]);
        $response=new WP_REST_Response(['html'=>self::render($attrs,true)]); $response->header('Cache-Control','no-store'); return $response;
    }
    static function rest_bible($request) {
        $rate=self::throttle(); if (is_wp_error($rate)) return $rate;
        $path=$request->get_param('path');
        if (!is_string($path) || !preg_match('~^translations(?:/[a-zA-Z0-9_-]{1,80}/books(?:/[a-zA-Z0-9_-]{1,100}/chapters/[1-9][0-9]{0,2})?)?$~D',$path)) return new WP_Error('path','Неверный путь.',['status'=>400]);
        $data=self::request('bible',$path); if (is_wp_error($data)) return $data;
        // BibleDesktop v1 wraps every API payload in {data: ...}; the browser contract stays flat.
        if (isset($data['data']) && is_array($data['data'])) $data=$data['data'];
        $response=new WP_REST_Response($data); $response->header('Cache-Control','no-store'); return $response;
    }
    static function rest_media($request) {
        $source=$request->get_param('source');
        if(!is_string($source)||Orthocal_Media_Cache::source($source)===false)return new WP_Error('source','Неверный адрес изображения.',['status'=>400]);
        $thumbnail=$request->get_param('thumbnail')==='1';
        $cached=Orthocal_Media_Cache::cached_url($source);
        if($cached){$url=$thumbnail?Orthocal_Media_Cache::thumbnail_url($source):$cached;$response=new WP_REST_Response(['url'=>$url]);$response->header('Cache-Control','private, max-age=86400');return $response;}
        $rate=self::media_throttle(); if (is_wp_error($rate)) return $rate;
        $url=$thumbnail?Orthocal_Media_Cache::thumbnail_url($source):Orthocal_Media_Cache::url($source);
        if(!$url)return new WP_Error('media','Изображение пока недоступно. Повторите позже.',['status'=>503]);
        $response=new WP_REST_Response(['url'=>$url]);$response->header('Cache-Control','no-store');return $response;
    }
    static function settings_page() { Orthocal_Admin::page(); }
}
