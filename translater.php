<?php	
	
	/*SETTINGS*/
	
	$mode='blang';
	//$mode='evobabel';
	$replace_all = true;
	$translate = true;
	
	/*SETTINGS*/
	
	define('MODX_API_MODE', true);
	define('IN_MANAGER_MODE', false);	
	include_once("index.php");	
	$modx->db->connect();	
	if (empty ($modx->config)) {
		$modx->getSettings();
	}	
	
	if (isset($_REQUEST['id'])){
		$id = $_REQUEST['id'];		
		if ($_REQUEST['element']=='chunks'){
			$table = $modx->getFullTableName('site_htmlsnippets');
			$field = 'snippet';
			} else {
			$table = $modx->getFullTableName('site_templates');
			$field = 'content';
		}
	}
	
	if (isset($_POST['text'])) $_POST['text'] = htmlspecialchars_decode($_POST['text']);
	
	if (isset($_GET['getCode'])){		
		echo htmlspecialchars($modx->db->getValue('Select '.$field.' from '.$table.' where id='.$id), ENT_QUOTES);		
		exit();
	}
	
	if ($mode=='blang') $def = $modx->db->getValue('Select `value` from '.$modx->getFullTableName('blang_settings').' where name="default"');
	if ($mode=='evobabel') $def = $modx->db->getValue('Select `code` from '.$modx->getFullTableName('languages_list').' where `gen`=1');
	
	if (isset($_REQUEST['translate'])){
		
		if ($mode=='blang'){
			/*for bLang*/
			$lngs = $modx->db->getValue('Select `value` from '.$modx->getFullTableName('blang_settings').' where name="languages"');
			
			$yet = $modx->db->getValue('Select id from '.$modx->getFullTableName('blang').' where
			'.$def.'="'.$modx->db->escape($_POST['text']).'"');
			
			$name = strip_tags($_POST['text']);
			$name = $modx->stripAlias($name);
			$name = str_replace('-','_',$name);
			$name = substr($name, 0, 8);
			$max = $modx->db->getValue('Select max(id) from '.$modx->getFullTableName('blang'));
			$max = $max+1;
			$name.='_'.$max;
			
			$out='<div class="form-group"><label for="name">Название параметра</label><input name="name" value="'.$name.'" class="form-control"></div>';
			
			if ($yet){			
				$res = $modx->db->query('Select '.str_replace('||',',',$lngs).' from 
				'.$modx->getFullTableName('blang').' where id='.$yet);
				$row = $modx->db->getRow($res);			
				foreach($row as $key => $val) {
					$out.='<div class="form-group"><label for="'.$key.'">'.$key.'</label><textarea name="'.$key.'" class="form-control" id="'.$key.'" rows="2">'.$val.'</textarea></div>';
				}
				echo $out;
				exit();
			}
			/*for bLang*/
		}
		
		if ($mode=='evobabel'){
			/*for evoBabel*/
			$lngs = $modx->db->getValue('Select GROUP_CONCAT(`code` SEPARATOR "||")  from '.$modx->getFullTableName('languages_list'));
			
			$yet = $modx->db->getValue('Select id from '.$modx->getFullTableName('lexicon').' where
			'.$def.'="'.$modx->db->escape($_POST['text']).'"');
			
			$name = strip_tags($_POST['text']);
			$name = $modx->stripAlias($name);
			$name = str_replace('-','_',$name);
			$name = substr($name, 0, 8);
			$max = $modx->db->getValue('Select max(id) from '.$modx->getFullTableName('lexicon'));
			$max = $max+1;
			$name.='_'.$max;
			
			$out='<div class="form-group"><label for="name">Название параметра</label><input name="name" value="'.$name.'" class="form-control"></div>';
			
			if ($yet){			
				$res = $modx->db->query('Select '.str_replace('||',',',$lngs).' from 
				'.$modx->getFullTableName('lexicon').' where id='.$yet);
				$row = $modx->db->getRow($res);			
				foreach($row as $key => $val) {
					$out.='<div class="form-group"><label for="'.$key.'">'.$key.'</label><textarea name="'.$key.'" class="form-control" id="'.$key.'" rows="2">'.$val.'</textarea></div>';
				}
				echo $out;
				exit();
			}
			/*for evoBabel*/
		}
		
		
		$out.= '<div class="form-group"><label for="'.$def.'">'.$def.'</label><textarea name="'.$def.'" class="form-control" id="'.$def.'" rows="2">'.$_POST['text'].'</textarea></div>';
		$tl = explode('||',$lngs);
		
		foreach($tl as $l){
			if ($l!=$def){
				if ($translate){
					
					$data = ['source'=>$_POST['text'],'lang'=>$def.'-'.$l];						
					$data_string = json_encode ($data, JSON_UNESCAPED_UNICODE);
					$ch = curl_init('https://fasttranslator.herokuapp.com/api/v1.0/text/to/text?'.http_build_query($data));
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_HEADER, false);
					$result = curl_exec($ch);
					curl_close($ch);
					$res = json_decode($result,1);
										
				}
				if ($res['data']) $text = $res['data'];
				else $text = $_POST['text'];
				$out.= '<div class="form-group"><label for="'.$l.'">'.$l.'</label><textarea name="'.$l.'" class="form-control" id="'.$l.'" rows="2">'.$text.'</textarea></div>';
			}
		}
		echo $out;
		exit();		
	}

if (isset($_REQUEST['setChanges'])){	
	foreach($_POST as $key => $val) $_POST[$key] = $modx->db->escape($val);
	
	if ($mode=='blang')	{
		$phx = '[(__'.$_POST['name'].')]';
		$modx->db->insert($_POST,$modx->getFullTableName('blang'));
	}
	if ($mode=='evobabel') {
		$phx='[%'.$_POST['name'].'%]';
		$modx->db->insert($_POST,$modx->getFullTableName('lexicon'));
	}
	
	if (!$replace_all) $where = ' where id='.$id;
	
	$modx->db->query("UPDATE ".$modx->getFullTableName('site_htmlsnippets')." SET snippet = REPLACE (snippet, '".$modx->db->escape($_POST[$def])."', '".$phx."')".$where);
	$modx->db->query("UPDATE ".$modx->getFullTableName('site_templates')." SET content = REPLACE (content, '".$modx->db->escape($_POST[$def])."', '".$phx."')".$where);
	
	echo htmlspecialchars($modx->db->getValue('Select '.$field.' from '.$table.' where id='.$id), ENT_QUOTES);		
	exit();		
}
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Set multiLang placeholders</title>
		<link rel="stylesheet" href="https://liber.pro/assets/css/bootstrap.min.css">
		<link rel="preconnect" href="https://fonts.gstatic.com">
		<link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300&display=swap" rel="stylesheet">
		<style>
			*{font-family: 'Ubuntu', sans-serif;}
			#translate{text-align:center; line-height:100px; height:100px; width:100px; border-radius:50%; box-shadow:1px 1px 1px black;z-index:22; position:fixed; bottom:20px; right:20px; cursor:pointer;}
		</style>
	</head>
	<body>
		<div id="translate">
			<img src="https://ipleak.org/cpp.png" style="width:64px;height:64px;">
		</div>
		<section>
			<div class="container">
				<div class="jumbotron">
					<div class="row">
						<div class="col-sm-6 col-xs-12">
							<select id="element" class="form-control" style="width:100%;">	
								<option value="" >Элемент</option>
								<option value="templates" <?php if ($_GET['element']=='templates') echo 'selected="selected"';?>>Шаблоны</option>
								<option value="chunks" <?php if ($_GET['element']=='chunks') echo 'selected="selected"';?>>Чанки</option>
							</select>
						</div>
						<div class="col-sm-6 col-xs-12 sub">
							<div id="stub" style="    width: 100%;height: 34px;background: #d4d4d4;border-radius: 5px; <? if (isset($_GET['getCodeHis'])) echo 'display:none;';?>"></div>
							<select id="templates" class="form-control" style="width:100%; <? if ($_GET['element']!='templates') echo 'display:none;';?>">	
								<option value="">Не выбрано</option>
								<?php
									$res = $modx->db->query('SELECT '.$modx->getFullTableName('categories').'.`category`,'.$modx->getFullTableName('site_templates').'.`category` as cat FROM '.$modx->getFullTableName('site_templates').' 
									left join '.$modx->getFullTableName('categories').'
									on '.$modx->getFullTableName('categories').'.`id` = '.$modx->getFullTableName('site_templates').'.`category`
									group by '.$modx->getFullTableName('site_templates').'.`category`');
									while ($row = $modx->db->getRow($res))
									{
										if (!$row['category']) $name = 'Без категории';
										else $name = $row['category'];
										
										echo '<optgroup label="'.$name.'">';
										$res2 = $modx->db->query('Select * from '.$modx->getFullTableName('site_templates').' where category='.$row['cat'].' order by templatename');
										while ($row2 = $modx->db->getRow($res2))
										{
											if (($_GET['element']=='templates') && ($_GET['id']==$row2['id'])) $selected = ' selected="selected"';
											else $selected='';
											echo '<option value="'.$row2['id'].'" '.$selected.'>'.$row2['templatename'].'</option>';;
										}
										echo '</optgroup>';
										
									}
								?>
							</select>		
							<select id="chunks" class="form-control" style="width:100%;  <? if ($_GET['element']!='chunks') echo 'display:none;';?>">	
								<option value="">Не выбрано</option>
								<?php
									$res = $modx->db->query('SELECT '.$modx->getFullTableName('categories').'.`category`,'.$modx->getFullTableName('site_htmlsnippets').'.`category` as cat FROM '.$modx->getFullTableName('site_htmlsnippets').' 
									left join '.$modx->getFullTableName('categories').'
									on '.$modx->getFullTableName('categories').'.`id` = '.$modx->getFullTableName('site_htmlsnippets').'.`category`
									group by '.$modx->getFullTableName('site_htmlsnippets').'.`category`');
									while ($row = $modx->db->getRow($res))
									{
										if (!$row['category']) $name = 'Без категории';
										else $name = $row['category'];									
										
										echo '<optgroup label="'.$name.'">';
										$res2 = $modx->db->query('Select * from '.$modx->getFullTableName('site_htmlsnippets').' where category='.$row['cat'].' order by name');
										while ($row2 = $modx->db->getRow($res2))
										{
											if (($_GET['element']=='chunks') && ($_GET['id']==$row2['id'])) $selected = ' selected="selected"';
											else $selected='';
											echo '<option value="'.$row2['id'].'" '.$selected.'>'.$row2['name'].'</option>';
										}
										echo '</optgroup>';									
									}
								?>
							</select>
						</div>
						<div class="col-md-12">
							
							<? if (!isset($_GET['getCodeHis'])){?>
								<div id="code" style="margin-top: 20px;font-size: 18px;">
									<div id="img" style="text-align:center; ">
										<img src="https://adwit.ru/wp-content/uploads/2018/04/coding-isometric-01-768x714.png">						
									</div>								
								</div>								
								<? } else {
									echo '<div id="code" style="white-space:pre-line;margin-top: 20px;font-size: 18px;">';
									echo htmlspecialchars($modx->db->getValue('Select '.$field.' from '.$table.' where id='.$id), ENT_QUOTES);							
									echo '</div>';
								} ;?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
	<div id="myModal" class="modal fade">
		<div class="modal-dialog">
			<div class="modal-content">				
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
					<h4 class="modal-title">Вставка плейсхолдеров</h4>
				</div>				
				<div class="modal-body">
					<form>
					</form>
				</div>				
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
					<button type="button" class="btn btn-success" data-dismiss="modal" id="setChanges">Внести изменения</button>
				</div>
			</div>
		</div>
	</div> 
	<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>		
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script type="text/javascript">
		$(function() {
			
			$('#element').change(function(){	
				console.log(location.pathname);
				var val = $(this).val();
				$('.sub > *').hide();
				$('#'+val).show();
			});
			
			$('.sub select').change(function(){
				var element = $('#element').val();
				var id = $(this).val();
				$.ajax({
					type: 'post', url: location.pathname+'?getCode', data: '&element='+element+'&id='+id,
					success: function(result){
						var uri = location.pathname +  '?getCodeHis&element='+element+'&id='+id;
						var histAPI = !!(window.history && history.pushState);
						if (histAPI) history.replaceState({uri: uri}, null, uri);
						
						$('#code').css({"white-space":"pre-line"});							
						$('#code').html(result);
					}
				});
			});					
			
			$('#translate').click(function(){
				$('.jumbotron').animate({"opacity":"0.6"},600);
				var text = window.getSelection().toString();					
				$.ajax({
					type: 'post', url: location.pathname+'?translate', data: '&text='+text,
					success: function(result){
						console.log(result);
						$('.jumbotron').animate({"opacity":"1"},600);
						$('.modal-body form').html(result);	
						$("#myModal").modal('show');
					}
				});
			});
			
			$('#setChanges').click(function(){
				$('.jumbotron').animate({"opacity":"0.6"},600);
				var id = $('.sub select:visible').val();
				$.ajax({
					type: 'post',
					url: location.pathname+'?setChanges&element='+$('#element').val()+'&id='+id,
					data: $('.modal-body form').serialize(),					
					success: function(result){
						console.log(result);	
						$('#code').html(result);
						$('.jumbotron').animate({"opacity":"1"},600);
						$("#myModal").modal('hide');
					}
				});
			});
		});
	</script>
</body>
</html>
