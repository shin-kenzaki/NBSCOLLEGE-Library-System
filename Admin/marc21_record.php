<?php
session_start();
include '../db.php';

function formatMARC21Date($date) {
    return date('YmdHis', strtotime($date));
}

if (isset($_GET['id'])) {
    $book_id = $_GET['id'];
    $query = "SELECT 
        b.*,
        GROUP_CONCAT(DISTINCT 
            CONCAT(
                c.role, ':', 
                w.lastname, ', ', 
                w.firstname, 
                CASE WHEN w.middle_init IS NOT NULL AND w.middle_init != '' 
                    THEN CONCAT(' ', w.middle_init, '.') 
                    ELSE '' 
                END
            ) 
            ORDER BY 
                FIELD(c.role, 'Author', 'Co-Author', 'Editor'),
                w.lastname, 
                w.firstname 
            SEPARATOR '|'
        ) as contributors,
        p.publisher,
        p.place,
        pub.publish_date
    FROM books b
    LEFT JOIN contributors c ON b.id = c.book_id
    LEFT JOIN writers w ON c.writer_id = w.id
    LEFT JOIN publications pub ON b.id = pub.book_id
    LEFT JOIN publishers p ON pub.publisher_id = p.id
    WHERE b.id = ?
    GROUP BY b.id";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MARC21 Record</title>
    <style>
        .marc-field {
            margin: 10px 0;
            font-family: monospace;
        }
        .field-tag {
            font-weight: bold;
            margin-right: 10px;
        }
        .subfield {
            margin-left: 20px;
        }
    </style>
</head>
<body>
    <?php include 'inc/header.php'; ?>

    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">MARC21 Record - <?= htmlspecialchars($book['title']) ?></h6>
            </div>
            <div class="card-body">
                <div class="marc-record">
                    <!-- 001 Control Number -->
                    <div class="marc-field">
                        <span class="field-tag">001</span>
                        <span class="field-content"><?= $book['accession'] ?></span>
                    </div>

                    <!-- 005 Date and Time -->
                    <div class="marc-field">
                        <span class="field-tag">005</span>
                        <span class="field-content"><?= formatMARC21Date($book['date_added']) ?></span>
                    </div>

                    <!-- 020 ISBN -->
                    <div class="marc-field">
                        <span class="field-tag">020</span>
                        <span class="field-content">‡a<?= $book['ISBN'] ?></span>
                    </div>

                    <!-- 050 Call Number -->
                    <div class="marc-field">
                        <span class="field-tag">050</span>
                        <span class="field-content">‡a<?= $book['call_number'] ?></span>
                    </div>

                    <?php
                    // Process contributors
                    $contributors = explode('|', $book['contributors']);
                    $author = '';
                    $coauthors = [];
                    $editors = [];
                    
                    foreach ($contributors as $contributor) {
                        list($role, $name) = explode(':', $contributor);
                        if ($role === 'Author' && empty($author)) {
                            $author = $name;
                        } elseif ($role === 'Co-Author') {
                            $coauthors[] = $name;
                        } elseif ($role === 'Editor') {
                            $editors[] = $name;
                        }
                    }
                    ?>

                    <!-- 100 Main Entry-Personal Name -->
                    <?php if (!empty($author)): ?>
                    <div class="marc-field">
                        <span class="field-tag">100</span>
                        <span class="field-content">1#‡a<?= $author ?>‡eauthor</span>
                    </div>
                    <?php endif; ?>

                    <!-- 245 Title Statement -->
                    <div class="marc-field">
                        <span class="field-tag">245</span>
                        <span class="field-content">10‡a<?= $book['title'] ?>
                            <?php if (!empty($book['contributors'])): ?>
                            ‡c<?= str_replace('|', ', ', $book['contributors']) ?>
                            <?php endif; ?>
                        </span>
                    </div>

                    <!-- 260 Publication Information -->
                    <div class="marc-field">
                        <span class="field-tag">260</span>
                        <span class="field-content">
                            ‡a<?= $book['place'] ?>
                            ‡b<?= $book['publisher'] ?>
                            ‡c<?= $book['publish_date'] ?>
                        </span>
                    </div>

                    <!-- 300 Physical Description -->
                    <div class="marc-field">
                        <span class="field-tag">300</span>
                        <span class="field-content">
                            ‡a<?= $book['total_pages'] ?> p.
                            ‡c<?= $book['height'] ?> x <?= $book['width'] ?> cm
                        </span>
                    </div>

                    <!-- 650 Subject Added Entry -->
                    <?php if (!empty($book['subject_detail'])): 
                        $subjects = explode('|', $book['subject_detail']);
                        foreach ($subjects as $subject): ?>
                    <div class="marc-field">
                        <span class="field-tag">650</span>
                        <span class="field-content">#0‡a<?= $subject ?></span>
                    </div>
                    <?php endforeach; endif; ?>

                    <!-- 700 Added Entries for Co-Authors and Editors -->
                    <?php 
                    foreach ($coauthors as $coauthor): ?>
                    <div class="marc-field">
                        <span class="field-tag">700</span>
                        <span class="field-content">1#‡a<?= $coauthor ?>‡eco-author</span>
                    </div>
                    <?php endforeach;
                    foreach ($editors as $editor): ?>
                    <div class="marc-field">
                        <span class="field-tag">700</span>
                        <span class="field-content">1#‡a<?= $editor ?>‡eeditor</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include '../Admin/inc/footer.php' ?>
</body>
</html>
<?php } ?>
