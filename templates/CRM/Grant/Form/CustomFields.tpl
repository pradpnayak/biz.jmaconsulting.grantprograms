{if $view_form}
<div id="field_page_new">
      <p></p>
      <div class="form-item">
        {strip}
        {* handle enable/disable actions*}
         {include file="CRM/common/enableDisable.tpl"}
        <table class="selector">
          <tr class="columnheader">
            <th>{ts}Label{/ts}</th>
            <th>{ts}Value{/ts}</th>
	    <th>{ts}Mark{/ts}</th>
            <th>{ts}Default{/ts}</th>
            <th>{ts}Order{/ts}</th>
            <th>{ts}Enabled?{/ts}</th>
            <th>&nbsp;</th>
          </tr>
          {foreach from=$customOption item=row key=id}
            <tr id="OptionValue-{$id}"class="crm-entity {cycle values="odd-row,even-row"} {$row.class} crm-custom_option {if NOT $row.is_active} disabled{/if}">
              <td><span class="crm-custom_option-label crm-editable crmf-label">{$row.label}</span></td>
              <td><span class="crm-custom_option-value disabled-crm-editable" data-field="value" data-action="update">{$row.value}</span></td>
              <td class="crm-custom_option-description disabled-crm-editable" data-field="description" data-action="update">{$row.description}</td>
              <td class="crm-custom_option-default_value crmf-value">{$row.default_value}</td>
              <td class="nowrap crm-custom_option-weight crmf-weight">{$row.weight}</td>
              <td id="row_{$id}_status" class="crm-custom_option-is_active crmf-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
              <td>{$row.action|replace:'xx':$id}</td>
            </tr>
          {/foreach}
          </table>
        {/strip}

        <div class="action-link">
            <a href="{crmURL q="reset=1&action=add&fid=$fid&gid=$gid"}" class="button"><span><div class="icon add-icon"></div> {ts 1=$fieldTitle}Add Option for '%1'{/ts}</span></a>
        </div>
      </div>
    </div>
{/if}
{if $form.description}
	<div id="form_marks" class="crm-custom_option-form-block-value">
	<tr class="crm-custom_option-form-block-description">
            <td class="label">{$form.description.label}</td>
            <td>{$form.description.html}</td>
        </tr>
        </div>
{/if}
{* Custom Field Edit *}
{if $edit_form}
<table id="optionField_new">
  <tr>
        <th>&nbsp;</th>
        <th> {ts}Default{/ts}</th>
        <th> {ts}Label{/ts}</th>
        <th> {ts}Value{/ts}</th>
        {if $form.option_description }
	<th> {ts}Mark{/ts}</th>
        {/if}
        <th> {ts}Weight{/ts}</th>
        <th> {ts}Active?{/ts}</th>
    </tr>

  {section name=rowLoop start=1 loop=12}
  {assign var=index value=$smarty.section.rowLoop.index}
  <tr id="optionField_{$index}" class="form-item {cycle values="odd-row,even-row"}">
        <td>
        {if $index GT 1}
            <a onclick="showHideRow({$index}); return false;" name="optionField_{$index}" href="#" class="form-link"><img src="{$config->resourceBase}i/TreeMinus.gif" class="action-icon" alt="{ts}hide field or section{/ts}"/></a>
        {/if}
        </td>
      <td>
    <div id="radio{$index}" style="display:none">
         {$form.default_option[$index].html}
    </div>
    <div id="checkbox{$index}" style="display:none">
         {$form.default_checkbox_option.$index.html}
    </div>
      </td>
      <td> {$form.option_label.$index.html}</td>
      <td> {$form.option_value.$index.html}</td>
      {if $form.option_description }
      <td> {$form.option_description.$index.html}</td>
      {/if}
      <td> {$form.option_weight.$index.html}</td>
       <td> {$form.option_status.$index.html}</td>
  </tr>
    {/section}
    </table>
{/if}
{* Custom Field Edit *}
{literal}
<script type="text/javascript">
cj(document).ready( function(){

cj('div#field_page').replaceWith(cj('div#field_page_new'));
cj('div#form_marks').insertAfter(cj('tr.crm-custom_option-form-block-value'));
cj('div#form_marks').replaceWith('<tr class="crm-custom_option-form-block-description"><td class="label">{/literal}{$form.description.label}{literal}</td><td>{/literal}{$form.description.html}{literal}</td></tr>');
cj('table#optionField').replaceWith(cj('table#optionField_new'));
});
</script>
{/literal}