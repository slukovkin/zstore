<table class="ctable" border="0" cellspacing="0" cellpadding="2">
    <tr>
        <td colspan="4" align="center">
            <b> Угода № {{document_number}} вiд {{date}}</b> <br>
        </td>
    </tr>

 
 
    <tr>
        <td colspan="4">
            <b>Компанiя:</b> {{comp}}
        </td>
    </tr>
   <tr>
        <td colspan="4">
            <b>Контрагент:</b> {{customer}}
        </td>
    </tr>
   
    {{#emp}}
    <tr>
        <td colspan="4">
            <b>Вiдповiдальний менеджер:</b> {{emp}}
        </td>
    </tr>
    {{/emp}}
    <tr>
        <td colspan="4">
            <b>Дата  закiнчення:</b> {{dateend}}
        </td>
    </tr>
    
    <tr>
        <td colspan="4">
            {{notes}}
        </td>
    </tr>


</table>


