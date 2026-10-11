<?php
if (!defined('ABSPATH')) exit;

/** Source-coordinate matches do not establish verse mapping for other translations. */
final class Orthocal_Lectionary {
    static function presentation($reading,$language) {
        $empty=['label'=>'','details'=>''];
        if(!is_array($reading))return $empty;
        $match=$reading['lectionary']??null;
        if(!is_array($match) || ($match['basis']??'')!=='exact-source-coordinates' || !in_array($match['status']??'',['exact-reference-match','ambiguous'],true))return $empty;
        if(!is_array($match['candidates']??null))return $empty;
        $candidates=[];$identities=[];
        foreach(($match['candidates']??[]) as $candidate) {
            if(!is_array($candidate) || !in_array($candidate['kind']??'',['gospel','apostle'],true)
                || !is_string($candidate['book']??null) || !preg_match('/^[1-3]?[A-Za-z]+$/D',$candidate['book'])
                || !(is_int($candidate['number']??null)||is_string($candidate['number']??null))
                || !preg_match('/^[1-9][0-9]{0,5}$/D',(string)$candidate['number'])
                || !is_string($candidate['reference']??null) || !is_string($candidate['source_variant']??null)
                || !is_string($candidate['source_url']??null))return $empty;
            $candidates[]=$candidate;
            $identities[$candidate['book'].'-'.$candidate['number']]=$candidate['number'];
        }
        if(!$candidates)return $empty;
        $exact=$match['status']==='exact-reference-match' && count($identities)===1;
        $label=$exact?' · '.Orthocal_Plugin::ui('Зачало',$language).' '.reset($identities):'';
        $html='<details class="oc-lectionary"><summary>'.esc_html(Orthocal_Plugin::ui($exact?'Источник зачала':'Возможные соответствия зачал',$language)).'</summary><ul>';
        foreach($candidates as $candidate) {
            $title=$candidate['reference'].' · '.Orthocal_Plugin::ui('Зачало',$language).' '.$candidate['number'];
            if($candidate['source_variant']!=='')$title.=' ('.$candidate['source_variant'].')';
            $html.='<li><a href="'.esc_url($candidate['source_url']).'" target="_blank" rel="noopener noreferrer">'.esc_html($title).'</a></li>';
        }
        $html.='</ul><p class="oc-muted">'.esc_html(Orthocal_Plugin::ui('Соответствие относится к координатам источника; нумерация стихов других переводов отдельно не проверена.',$language)).'</p></details>';
        return ['label'=>$label,'details'=>$html];
    }
}
