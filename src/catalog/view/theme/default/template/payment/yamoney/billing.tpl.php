
<input type="hidden" name="formId" value="<?php echo htmlspecialchars($formId); ?>" />
<input type="hidden" name="narrative" value="<?php echo htmlspecialchars($narrative); ?>" />
<div style="padding-bottom: 20px;">
    <label for="ya-fio">ФИО плательщика</label>
    <input type="text" name="fio" id="ya-fio" value="<?php echo htmlspecialchars($fio); ?>" />
    <div id="ya-fio-error"></div>
</div>
<input type="hidden" name="sum" value="<?php echo htmlspecialchars($sum); ?>" data-type="number" >
<input type="hidden" name="quickPayVersion" value="2" >
