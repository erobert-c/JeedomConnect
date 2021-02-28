<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

?>

<style>
  .required:after {
    content:" *";
    color: red;
  }
  #widgetImg {
	  display:block;
	  margin-left:auto;
	  margin-right:auto;
	  width: 100px;
	  margin-bottom:25px;
	  margin-top:15px;
  }
  .description {
	  color:var(--al-info-color);
	  font-size:11px;
  }
  .borderLef{
	border-left: 1px solid #ccc;
  }

  .btn-supp{
	background-color: var(--al-danger-color) !important;
  }
</style>

<div>
  	<div style="display:none;" id="widget-alert"></div>
  	<div class="input-group pull-right" style="display:inline-flex;">
		<span class="input-group-btn">
			<!-- Les balises <a></a> sont volontairement fermées à la ligne suivante pour éviter les espaces entre les boutons. Ne pas modifier -->
			<!-- <a class="btn btn-sm btn-danger roundedRight" data-action="remove" onclick="removeWidget()"><i class="fas fa-minus-circle"></i> {{Supprimer}}
			</a><a class="btn btn-sm btn-success " data-action="save" onclick="saveWidget()"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
			</a> -->
		</span>
	</div>
	<div class="col-sm-12">
		<div class="col-sm-3">
			<h3>Choix du widget</h3>
			<select name="widgetsList" id="widgetsList-select"  onchange="refreshAddWidgets();">
			</select>
			<img id="widgetImg" />
			<div class="alert alert-info" id="widgetDescription">
			</div>
		</div>
		
		<div class="col-sm-9 borderLef">
			<h3 style="margin-left:25px;">Options du widget</h3><br>
			<div style="margin-left:25px; font-size:12px; margin-top:-20px; margin-bottom:15px;">Les options marquées d'une étoile sont obligatoires.</div>
			<form class="form-horizontal widgetForm" style="overflow: hidden;">
				<ul id="widgetOptions" style="padding-left:10px; list-style-type:none;">
				</ul>
			</form>
		</div>
  	</div>
  	
</div>

 <?php include_file('desktop', 'JeedomConnect', 'js', 'JeedomConnect');?>
 <?php include_file('desktop', 'assistant.JeedomConnect', 'js', 'JeedomConnect');?>