menrol_init();

function menrol_init()
{
	var userrole = document.getElementById("userroles").value;

	if (document.assignform.enrollment[0].checked) {
		var checkvalue = document.assignform.enrollment[0].value;
		enrollment_type(checkvalue,userrole);

	}
	else if(document.assignform.enrollment[1].checked)
	{
		   var checkvalue = document.assignform.enrollment[1].value;
		   enrollment_type(checkvalue,userrole);

	}

}

function validate()
{
	if(document.getElementById("userroles").value == "selectrole")
	{
		alert("Please select role ");
		return false;
	}
	else
	{
		return true;
	}
}

function validation()
{
	if(document.getElementById("selecteduser").value == "")
	{
		alert("Please select user");
		return false;
	}

	if(document.getElementById("selectedcourse").value == "")
	{
		alert("Please select courses");
		return false;
	}
	return true;

}

var xmlHttp;
function enrollment_type(check)
{
	xmlHttp=GetXmlHttpObject();
	if (xmlHttp==null)
  	{
  		alert ("Your browser does not support AJAX!");
  		return;
  	} 
	var url="ajax_assign_multiple_course.php";
	var roleid = document.getElementById("userroles").value;
	var enrol_duration = document.getElementById("enrol_duration").value;
	var recovergrades = 0;
	if(document.getElementById("recovergrades").checked == true)
	{
		recovergrades = 1;
	}
	if(check != "add" && check != "remove")
	{
			if (document.assignform.enrollment[0].checked) {
				var check = document.assignform.enrollment[0].value;
			   
			} 
			else if(document.assignform.enrollment[1].checked)
			{
				   var check = document.assignform.enrollment[1].value;
			}
	}
	
	var temp ="";
	if(check == "add")
	{
		if(document.getElementById("addcourseselect"))
		{
			
			if(document.getElementById("userid").value == "")
			{
				alert("Please select user first");
				return false;
			}
			else if(document.getElementById("addcourseselect").value == "")
			{
				alert("Please select potential course")
				return false
			}
			else
			{
				for (var i=0; i < document.getElementById("addcourseselect").length; i++)
				{		
					if(document.getElementById("addcourseselect")[i].selected == true)
					{
						temp=temp + document.getElementById("addcourseselect")[i].value + ",";
					}
				}
			}
		}
	}
	else if(check == "remove")
	{
		if(document.getElementById("removecourseselect"))
		{
			
			if(document.getElementById("removecourseselect").value == "")
			{
				alert("Please select existing course");
				return false;
			}
			else
			{
				for (var i=0; i < document.getElementById("removecourseselect").length; i++)
				{		
					if(document.getElementById("removecourseselect")[i].selected == true)
					{
						temp=temp + document.getElementById("removecourseselect")[i].value + ",";
					}
				}
			}
		}	
	}
	
	document.getElementById('load_image').style.display="block";
	if(document.getElementById("userid") )
	{
		var userid = document.getElementById("userid").value;
		url=url+"?enrollmenttype="+check+"&roleid="+roleid+"&userid="+userid+"&courseid="+temp+"&recovergrades="+recovergrades+"&enrol_duration="+enrol_duration;
	}
	else
	{
		url=url+"?enrollmenttype="+check+"&roleid="+roleid;
	}
		
	//alert(url);
	xmlHttp.onreadystatechange=enrollstateChanged;
	xmlHttp.open("GET",url,true);
	xmlHttp.send(null);
} 

function enrollstateChanged() 
{ 
	if (xmlHttp.readyState==4)
	{ 
		document.getElementById('load_image').style.display="none";
		document.getElementById("displaytype").innerHTML=xmlHttp.responseText;
	}
}

function GetXmlHttpObject()
{
	var xmlHttp=null;
	try
	{
		// Firefox, Opera 8.0+, Safari
		xmlHttp=new XMLHttpRequest();
	}
	catch (e)
	{
		// Internet Explorer
		try
		{
			xmlHttp=new ActiveXObject("Msxml2.XMLHTTP");
		}
		catch (e)
		{
			xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
		}
	}
	return xmlHttp;
}