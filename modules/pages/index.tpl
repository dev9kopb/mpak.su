<? if($_GET['id']): ?>
	<? if($index = $tpl['index'][ $_GET['id'] ]): ?>
		<?/* if($menu_index = rb($tpl['menu_index'], "href", array_flip(array($_SERVER['REQUEST_URI'])))): ?>
			<div class="navigation">
				<? $function = function($menu_index) use(&$function, $tpl, $arg){ ?>
					<? if($mi = $tpl['menu_index'][ $menu_index['index_id'] ]) $function($mi); ?>
					 &raquo; <a href="<?=$menu_index['href']?>"><?=$menu_index['name']?></a>
				<? }; $function($tpl['menu_index'][ $menu_index['index_id'] ]); ?>
			</div>
		<? endif;*/ ?>

		<? if($cat = $tpl['cat'][ $index['cat_id'] ]): ?>
			<div class="bradcrumbs">
				<? $function = function($cat) use(&$function, $tpl, $arg){ ?>
					<? if($c = $tpl['cat'][ $cat['cat_id'] ]): ?>
						<? $function($c) ?>
					<? endif; ?> &raquo; <a href="/<?=$arg['modname']?>:cat/<?=$cat['id']?>"><?=$cat['name']?></a>
				<? }; $function($tpl['cat'][ $index['cat_id'] ]); ?> &raquo; <?=$index['name']?>
			</div>
		<? endif; ?>
		
		<div><?=aedit("/?m[{$arg['modpath']}]=admin&r={$conf['db']['prefix']}{$arg['modpath']}_index&where[id]=". (int)$index['id'])?></div>
		<h1><?=$index['name']?></h1>
		<div><?=$index['text']?></div>
		<div style="margin-top:20px;"><?=$conf['settings']['comments']?></div>
	<? else: ?>
		<div style="margin-top:150px; text-align:center;">
			Данная страница не найдена на сайте.<br />Возможно она была удалена.
			<? if($arg['access'] > 3): ?>
				<script>
					$(function(){
						$("a.add").on("click", function(){
							$.post("/<?=$arg['modname']?>/<?=$_GET['id']?>/null", {id:<?=$_GET['id']?>}, function(data){
								if(isNaN(data)){ alert(data) }else{
									document.location.reload(true);
								}
							});
						});
					});
				</script>
				<a class="add" href="javascript:">Создать</a>
			<? endif; ?>
		</div>
	<? endif; ?>
<? elseif($cat = rb($tpl['cat'], "id", $_GET['cat_id'])): ?>
	<div>
		<h1><?=$cat['name']?></h1>
		<ul>
			<? foreach(rb($tpl['index'], "cat_id", "id", $cat['id']) as $index): ?>
				<li>
					<a href="/<?=$arg['modname']?>/<?=$index['id']?>"><?=$index['name']?></a>
				</li>
			<? endforeach; ?>
		</ul>
	</div>
<? elseif($tpl['pages']): ?>
	<ul>
		<? foreach($tpl['pages'] as $cat_id=>$cats): ?>
			<li>
				<b><?=$tpl['cat'][ $cat_id ]['name']?></b> [<?=$tpl['cat'][ $cat_id ]['cnt']?>]
				<ul>
					<? foreach($cats as $v): ?>
						<li><a href="/<?=$arg['modname']?>/<?=$v['id']?>"><?=$v['name']?></a></li>
					<? endforeach; ?>
				</ul>
			</li>
		<? endforeach; ?>
	</ul>
<? endif; ?>