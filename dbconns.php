// Fetch classrooms and their available resources using $dbconns
$sql = "
SELECT 
    c.id AS classroom_id, 
    c.name AS classroom_name, 
    c.floor, 
    r.resource_name, 
    r.quantity, 
    r.availability
FROM classrooms c
LEFT JOIN resources r 
    ON c.id = r.classroom_id
WHERE r.availability = 1
ORDER BY c.floor, c.name, r.resource_name
";

$result = $dbconns->query($sql);

$classrooms = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $cid = $row['classroom_id'];
        if (!isset($classrooms[$cid])) {
            $classrooms[$cid] = [
                'name' => $row['classroom_name'],
                'floor' => $row['floor'],
                'resources' => []
            ];
        }
        $classrooms[$cid]['resources'][] = [
            'name' => $row['resource_name'],
            'quantity' => $row['quantity']
        ];
    }

    // Output
    foreach ($classrooms as $cid => $cinfo) {
        echo "Classroom: {$cinfo['name']} (Floor: {$cinfo['floor']})\n";
        echo "Resources:\n";
        foreach ($cinfo['resources'] as $res) {
            echo "- {$res['name']} : {$res['quantity']} available\n";
        }
        echo "\n";
    }
} else {
    echo "Database query failed: " . $dbconns->error;
}
