<?php
if (!defined('ABSPATH')) exit;

/** Full Bible Desktop works, kept separate from calendar assignments. */
final class Orthocal_Library {
    const LANGUAGES = ['ru'=>'Русский','cu'=>'Церковнославянский','cu-civil'=>'Церковнославянский — гражданский шрифт','de'=>'Deutsch','pl'=>'Polski','uk'=>'Українська'];

    static function language($a) {
        return ($a['text_language'] ?? '') ?: $a['lang'];
    }

    static function preferred_language($a,$counts) {
        $preferred=($a['text_language']??'')?:($a['lang']??'ru');
        if(!isset(self::LANGUAGES[$preferred]))$preferred='ru';
        $best=$preferred;$bestCount=(int)($counts[$preferred]??0);
        foreach(array_keys(self::LANGUAGES) as $language) {
            $count=(int)($counts[$language]??0);
            if($count>$bestCount){$best=$language;$bestCount=$count;}
        }
        return $best;
    }

    static function text_attributes($a,$text) {
        $language=$text['language']??self::language($a);
        $orthography=$language==='cu'?'traditional':'civil';
        return 'lang="'.esc_attr($language).'" data-orthography="'.$orthography.'"';
    }

    static function language_control($a,$languages=null) {
        if($languages===null && in_array($a['mode']??'', ['troparia','kontakia'], true))$languages=['ru','cu','cu-civil'];
        $languages=$languages===null?array_keys(self::LANGUAGES):array_values(array_intersect(array_keys(self::LANGUAGES),$languages));
        $html='<label>'.esc_html(Orthocal_Plugin::ui('Язык текста',$a['lang'])).' <select data-oc-library-language>';
        foreach($languages as $code)$html.='<option value="'.esc_attr($code).'" '.selected(self::language($a),$code,false).'>'.esc_html(self::LANGUAGES[$code]).'</option>';
        return $html.'</select></label>';
    }

    static function data($a) {
        $language=self::language($a);
        $collection=$a['mode']==='horologion'?'horologion':($a['mode']==='prayers'?'prayers':(in_array($a['mode'],['troparia','kontakia'],true)?'horologion-appendix':$a['mode']));
        $catalog=Orthocal_Plugin::request('bible','liturgical/works',['collection'=>$collection]);
        if(is_wp_error($catalog))return $catalog;
        $catalogWorks=$catalog['data']??[];
        if(in_array($a['mode'],['troparia','kontakia'],true))$catalogWorks=array_values(array_filter($catalogWorks,static fn($work)=>str_starts_with($work['slug'],'tropari-i-kondaki-')));
        if(($a['text_language']??'')==='') {
            $counts=array_fill_keys(array_keys(self::LANGUAGES),0);
            foreach($catalogWorks as $work)foreach(array_unique($work['available_languages']??[]) as $available)if(isset($counts[$available]))$counts[$available]++;
            $language=self::preferred_language($a,$counts);
        }
        $languages=array_values(array_unique(array_merge(...array_map(static fn($work)=>$work['available_languages']??[],$catalogWorks))));
        $works=array_values(array_filter($catalogWorks,static fn($work)=>in_array($language,$work['available_languages']??[],true)));
        $slug=$a['work']??'';
        $selected=null;
        foreach($works as $work)if($work['slug']===$slug)$selected=$work;
        if(!$selected && $slug==='') {
            $offices=['horologion'=>'horologion-vosstav-ot-sna','first-hour'=>'horologion-cas-pervyi','third-hour'=>'horologion-cas-tretii','sixth-hour'=>'horologion-cas-sestoi','ninth-hour'=>'horologion-cas-deviatyi','matins'=>'horologion-utrenia','vespers'=>'horologion-vecernia','compline'=>'horologion-maloe-povecerie','typica'=>'horologion-posledovanie-izobrazitelnyx'];
            foreach($works as $work)if($work['slug']===($offices[$a['office']]??''))$selected=$work;
            $selected??=$works[0]??null;
        }
        $version=null;
        if($selected) {
            $response=Orthocal_Plugin::request('bible','liturgical/works/'.rawurlencode($selected['slug']).'/versions/'.rawurlencode($language));
            if(is_wp_error($response))return $response;
            $version=$response['data']??null;
            if(($version['language']??null)!==$language)return new WP_Error('language','Источник вернул другую языковую версию.');
        }
        return ['library'=>true,'works'=>$works,'version'=>$version,'language'=>$language,'languages'=>$languages];
    }

    static function hymn_blocks($blocks,$mode) {
        if(!in_array($mode,['troparia','kontakia'],true))return $blocks;
        $target=$mode==='troparia'?'troparia':'kontakia';$active=null;$pending=[];$result=[];
        foreach($blocks as $block) {
            $text=trim((string)($block['text']??''));$type=null;
            if(preg_match('/^(?:(?:иной|другой)\s+)?тропар/iu',$text))$type='troparia';
            elseif(preg_match('/^(?:(?:иной|другой)\s+)?кондак/iu',$text))$type='kontakia';
            if(($block['kind']??'')==='heading') {
                if($type)$active=$type;
                $pending[]=$block;
                continue;
            }
            if($type)$active=$type;
            if($active===$target)foreach(array_merge($pending,[$block]) as $item)$result[]=$item;
            $pending=[];
        }
        return $result;
    }

    static function render($data,$a) {
        $languages=$data['languages']??null;
        $html='<div class="oc-service-controls">'.self::language_control($a,$languages);
        if($data['works']) {
            $html.='<label>'.esc_html(Orthocal_Plugin::ui('Раздел',$a['lang'])).' <select data-oc-library-work>';
            foreach($data['works'] as $work)$html.='<option value="'.esc_attr($work['slug']).'" '.selected($data['version']['slug']??'',$work['slug'],false).'>'.esc_html($work['title']).'</option>';
            $html.='</select></label>';
        }
        if($a['show_font_size']==='1')$html.='<label class="oc-library-font">'.esc_html(Orthocal_Plugin::ui('Размер текста',$a['lang'])).' <input data-oc-library-font type="range" min="16" max="40" value="24"></label>';
        $html.='</div>';
        $version=$data['version'];
        if(!$version)return $html.'<p class="oc-message">'.esc_html(Orthocal_Plugin::ui('На выбранном языке текст пока не добавлен.',$a['lang'])).'</p>'.self::sources($a);
        $html.='<article class="oc-reading-body oc-library-reader"><h3>'.esc_html($version['title']).'</h3><button type="button" data-oc-copy-reading>'.esc_html(Orthocal_Plugin::ui('Скопировать текст',$a['lang'])).'</button><div class="oc-verses oc-library-content" '.self::text_attributes($a,$version).'>';
        $blocks=self::hymn_blocks($version['blocks']??[],$a['mode']);
        foreach($blocks as $index=>$block) {
            if($index===0 && ($block['kind']??'')==='heading' && trim($block['text']??'')===trim($version['title']))continue;
            $tag=($block['kind']??'')==='heading'?'h4':'p';
            $html.='<'.$tag.(($block['kind']??'')==='rubric'?' class="oc-service-rubric"':'').'>';
            if(!empty($block['segments']))foreach($block['segments'] as $segment)$html.='<span'.(!empty($segment['rubric'])?' class="oc-service-rubric"':'').'>'.nl2br(esc_html($segment['text'])).'</span>';
            else $html.=nl2br(esc_html($block['text']??''));
            $html.='</'.$tag.'>';
        }
        return $html.'</div><p class="oc-text-source"><a href="'.esc_url($version['source_url']??'').'" target="_blank" rel="noopener noreferrer">'.esc_html($version['credit']??'Источник').'</a></p></article>';
    }

    static function sources($a) {
        $links=self::language($a)==='de'?['Orthodoxes Gebetbuch'=>'https://orthodoxia.de/gebete/gebetbuch']: (self::language($a)==='pl'?['Modlitwy prawosławne'=>'https://liturgia.cerkiew.pl/page.php?id=14']:[]);
        $html='';foreach($links as $title=>$url)$html.='<p><a href="'.esc_url($url).'" target="_blank" rel="noopener noreferrer">'.esc_html($title).' ↗</a></p>';
        return $html;
    }
}
