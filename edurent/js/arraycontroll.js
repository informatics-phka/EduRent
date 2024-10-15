var dep_array = new Array();
$('input[type="checkbox"]').on("click", DoSomething);

function  DoSomething(){
    if($(this).prop('checked') == true){
        if(dep_array[$(this).val()] == undefined){
            dep_array[$(this).val()] = 0;
        }
        dep_array[$(this).val()] += 1;
    }
    else{
        dep_array[$(this).val()] -= 1;
    }
    document.getElementById("department_"+$(this).val()).value=dep_array[$(this).val()];
}