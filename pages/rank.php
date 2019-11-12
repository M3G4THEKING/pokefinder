<?php
global $assetRepo;
global $conn;

$mon_name = json_decode(file_get_contents('https://raw.githubusercontent.com/cecpk/OSM-Rocketmap/master/static/data/pokemon.json'), true);

if(isset($_GET['mode'])){
    if($_GET['mode']!='raid' && $_GET['mode']!='pokemon'){
        $mode=' (Wild Pokemon)';
        $query = 'SELECT pokemon_id, count, rank FROM ( SELECT pokemon_id, count, row_number() OVER ( ORDER BY COUNT DESC, pokemon_id ASC) AS rank FROM ( SELECT pokemon_id, COUNT(pokemon_id) AS COUNT FROM pokemon GROUP BY pokemon_id ) a ) b WHERE pokemon_id is not null ORDER BY rank asc, pokemon_id asc';
        } else {
            if($_GET['mode']=='pokemon'){
                $mode=' (Wild Pokemon)';
                $query = 'SELECT pokemon_id, count, rank FROM ( SELECT pokemon_id, count, row_number() OVER ( ORDER BY COUNT DESC, pokemon_id ASC) AS rank FROM ( SELECT pokemon_id, COUNT(pokemon_id) AS COUNT FROM pokemon GROUP BY pokemon_id ) a ) b WHERE pokemon_id is not null ORDER BY rank asc, pokemon_id asc';
            }    
            if($_GET['mode']=='raid'){
                $mode=' (In Raids)';
                $query = 'SELECT pokemon_id, count, rank FROM ( SELECT pokemon_id, count, row_number() OVER ( ORDER BY COUNT DESC, pokemon_id ASC) AS rank FROM ( SELECT pokemon_id, COUNT(pokemon_id) AS COUNT FROM raid GROUP BY pokemon_id ) a ) b WHERE pokemon_id is not null ORDER BY rank asc, pokemon_id asc';
            }
        }
} else {
    $mode=' (Wild Pokemon)';
    $query = 'SELECT pokemon_id, count, rank FROM ( SELECT pokemon_id, count, row_number() OVER ( ORDER BY COUNT DESC, pokemon_id ASC) AS rank FROM ( SELECT pokemon_id, COUNT(pokemon_id) AS COUNT FROM pokemon GROUP BY pokemon_id ) a ) b WHERE pokemon_id is not null ORDER BY rank asc, pokemon_id asc';
}

$result = $conn->query($query);?>
<h3>Pokemon Seen Ranks<?=$mode?></h3>
[<a href="index.php?page=rank&mode=pokemon">Wild</a>][<a href="index.php?page=rank&mode=raid">Raids</a>]
<div class="table-responsive-sm">
<table id="rankTable" class="table table-striped table-bordered w-auto">
  <thead>
    <tr>
      <th>Rank</th>
      <th>Pokemon</th>
      <th>Seen x</th>
    </tr>
  </thead>
  <tbody>

<?php if($result && $result->num_rows >= 1 ) {
    while ($row = $result->fetch_object() ) {
?>
<tr>
<td><?=$row->rank?></td>
<td><img src="<?=$assetRepo . 'pokemon_icon_' . str_pad($row->pokemon_id, 3, 0, STR_PAD_LEFT) . '_00.png'?>" height="32" width="32"> <a href="index.php?page=seen&pokemon=<?=$row->pokemon_id?>"><?=$mon_name[$row->pokemon_id]['name']?></a></td>
<td><?=$row->count?></td>
</tr>
<?php
    }
}
?>
</tbody>
</table>
</div>