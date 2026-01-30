<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
  <script src="https://cdn.datatables.net/1.10.22/js/jquery.dataTables.js"></script>
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.22/css/jquery.dataTables.css">
  <link rel="stylesheet" type="text/css" href="https://datatables.net/media/css/site-examples.css">
    
<script type="text/javascript" src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">



 <meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


    <script src="https://cdn.datatables.net/rowgroup/1.4.1/js/dataTables.rowGroup.min.js"></script>
	

<script src="https://nightly.datatables.net/fixedcolumns/js/dataTables.fixedColumns.js"></script>	
<link href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.dataTables.min.css" rel="stylesheet" type="text/css" />	



	
<script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>
<link href="https://cdn.datatables.net/select/1.7.0/css/select.dataTables.min.css" rel="stylesheet">
    <style>


    .table td{
  font-size: 12x;
	    font-weight: bold;	
		  background: #1E4E79;
		    color: white;
    }
	
	    .table th{
  font-size: 12px;
		  color: white;
  background: #1E4E79;
    font-weight: bold;
    }
	



.red {
  background-color: red
}
.blue {
  background-color: #1E4E79;
  color: white;
}


div.scroll {
    overflow:auto;
}

</style>



     <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
	<script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
	
	
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.datatables.net/rowgroup/1.4.1/js/dataTables.rowGroup.min.js"></script>
	
<script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>
<link href="https://cdn.datatables.net/select/1.7.0/css/select.dataTables.min.css" rel="stylesheet">

	

  <button class="btn btn-default1" id="Ini" name='Ini' onclick="window.location.href='http://192.168.100.79/Comedor/PlatBacrocorp.php'"> <img src="Inicio.jpg" width="65" /> </button> &nbsp;&nbsp;
  <button class="btn btn-default115" id="Car" name='Carg' onclick="window.location.href='http://192.168.100.79/Comedor/uploadexcseg.php'"> <img src="Cargcon.png" width="50" /> </button>
  <button class="btn btn-default115" id="Edit" name='Edit' onclick="theFunction1();"> <img src="Contrato.jpg" width="70" /> </button>
  <button class="btn btn-default" id="Ec" name='Ec' onclick="theFunction();"> <img src="Basurero.jpg" width="50" /> </button> 
  <button class="btn btn-default1" id="EX" name='EX' onclick="ExportToExcel('xlsx')"> <img src="EXCEL.PNG" width="50" />   <button class="btn btn-default1" id="EX" name='EX' onclick="toCelsius();"> <img src="Aprobacion.PNG" width="40" /> </button><a href="http://192.168.100.79/Comedor/PruebaTabla.php?newpwd=Ope01?">RECARGAR</a> <div  id="demo" style=" font-size: 25px;;background-color:#2D6DA6;color:white;"><?php echo $value ?></div> </button>  <form method="post" action="<?php echo htmlspecialchars("http://192.168.100.79/Comedor/PruebaTabla.php?newpwd=Ope01?");?>">   <label for="exampleFormControlInput1">Centro de costos:</label>
      <select class="selectpicker" id="Ncliente" name='Ncliente' required>
    </select>
<button type="submit" class="btn btn-dark">Buscar</button>  
</form>


<table id="example" class="cell-border" width="100%">
<thead>
<th colspan="1" style="text-align:center">No</th>
<th colspan="1" style="text-align:center">Unidad</th>
<th colspan="1" style="text-align:center">Equipo</th>
<th <tr><th rowspan="2"class="blue">FECHA_ENT_OS</th><th colspan="1"class="blue">Operaciones</th></tr><tr><th class="blue">Enero</th></tr></th>
</thead>
</table>
</div>
