<?php
/**
 * Project files: documents uploaded to a project as information for its AI briefs (.txt, .md, .csv, .docx, .odt).
 * Only their text is kept (table project_files); briefs get it at download time, after the project information.
 * .docx and .odt are zip archives: read here with zlib, so the zip extension is not needed.
 */

const GEN_PFILE_MAX_BYTES = 3 * 1024 * 1024; // nginx allows 5 MB per request (deploy/nginx), and the upload is base64
const GEN_PFILE_MAX_CHARS = 50000;           // text kept per file
const GEN_PFILE_MAX_FILES = 10;
const GEN_PFILE_KINDS = ['txt' => 'txt', 'text' => 'txt', 'md' => 'txt', 'markdown' => 'txt', 'csv' => 'csv', 'tsv' => 'csv', 'docx' => 'docx', 'odt' => 'odt'];

function gen_pfiles_table(PDO $db): void
{
    $db->exec('CREATE TABLE IF NOT EXISTS project_files (id INTEGER PRIMARY KEY AUTOINCREMENT, project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
        name TEXT NOT NULL, kind TEXT NOT NULL, size INTEGER NOT NULL, text TEXT NOT NULL, created TEXT NOT NULL)');
}

/** The project's files without their text: id, name, kind, size, chars, created. */
function gen_pfiles_list(PDO $db, int $projectId): array
{
    gen_pfiles_table($db);
    $q = $db->prepare('SELECT id, name, kind, size, length(text) AS chars, created FROM project_files WHERE project_id = ? ORDER BY id');
    $q->execute([$projectId]);
    return $q->fetchAll();
}

/** Stores an uploaded file's text. Returns the stored row, or throws with a message for the user. */
function gen_pfile_add(PDO $db, int $projectId, string $name, string $data): array
{
    gen_pfiles_table($db);
    $name = trim(preg_replace('/[\x00-\x1f\/\\\\]+/', ' ', $name)) ?: 'file';
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if ($ext === 'doc' || $ext === 'rtf') {
        throw new InvalidArgumentException("$name: old Word (.doc) and .rtf files cannot be read. Save it as .docx (File → Save as) and upload that.");
    }
    $kind = GEN_PFILE_KINDS[$ext] ?? null;
    if ($kind === null) {
        throw new InvalidArgumentException("$name: only .txt, .md, .csv, .docx and .odt files can be uploaded.");
    }
    if (strlen($data) > GEN_PFILE_MAX_BYTES) {
        throw new InvalidArgumentException("$name: the file is over 3 MB.");
    }
    $q = $db->prepare('SELECT COUNT(*) FROM project_files WHERE project_id = ?');
    $q->execute([$projectId]);
    if ((int)$q->fetchColumn() >= GEN_PFILE_MAX_FILES) {
        throw new InvalidArgumentException('A project holds at most ' . GEN_PFILE_MAX_FILES . ' files. Delete one first.');
    }
    $text = match ($kind) {
        'txt' => gen_pfile_plain($data),
        'csv' => gen_pfile_csv(gen_pfile_plain($data)),
        'docx' => gen_pfile_docx($data, $name),
        'odt' => gen_pfile_odt($data, $name),
    };
    $text = trim(preg_replace("/\n{3,}/", "\n\n", preg_replace('/[ \t]+\n/', "\n", $text)));
    if ($text === '') {
        throw new InvalidArgumentException("$name: no text found in the file.");
    }
    if (mb_strlen($text) > GEN_PFILE_MAX_CHARS) {
        $text = mb_substr($text, 0, GEN_PFILE_MAX_CHARS) . "\n[… cut: only the first " . GEN_PFILE_MAX_CHARS . ' characters are kept]';
    }
    $db->prepare('INSERT INTO project_files (project_id, name, kind, size, text, created) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$projectId, mb_substr($name, 0, 160), $kind, strlen($data), $text, gen_now()]);
    $id = (int)$db->lastInsertId();
    return ['id' => $id, 'name' => $name, 'kind' => $kind, 'size' => strlen($data), 'chars' => mb_strlen($text)];
}

/** Text in UTF-8 from a text file: UTF-8 (with or without BOM), UTF-16 with BOM, or Windows-1251 (Russian Excel and Notepad). */
function gen_pfile_plain(string $s): string
{
    if (str_starts_with($s, "\xEF\xBB\xBF")) {
        $s = substr($s, 3);
    } elseif (str_starts_with($s, "\xFF\xFE") || str_starts_with($s, "\xFE\xFF")) {
        $s = mb_convert_encoding(substr($s, 2), 'UTF-8', str_starts_with($s, "\xFF\xFE") ? 'UTF-16LE' : 'UTF-16BE');
    } elseif (!mb_check_encoding($s, 'UTF-8')) {
        $s = mb_convert_encoding($s, 'UTF-8', 'Windows-1251');
    }
    return str_replace(["\r\n", "\r"], "\n", $s);
}

/** A CSV (comma, semicolon or tab separated) as a Markdown table: the first row is the header. */
function gen_pfile_csv(string $s): string
{
    $lines = array_values(array_filter(explode("\n", $s), fn($l) => trim($l) !== ''));
    if (!$lines) {
        return '';
    }
    $head = $lines[0];
    $sep = ',';
    foreach (["\t", ';', ','] as $c) {
        if (substr_count($head, $c) > substr_count($head, $sep)) {
            $sep = $c;
        }
    }
    $rows = [];
    $buf = '';
    foreach ($lines as $l) { // a quoted cell may hold line breaks: join lines until the quotes balance
        $buf = $buf === '' ? $l : "$buf\n$l";
        if (substr_count($buf, '"') % 2 === 0) {
            $rows[] = str_getcsv($buf, $sep, '"', '');
            $buf = '';
        }
    }
    $w = max(array_map('count', $rows));
    $cell = fn($v) => str_replace(['|', "\n"], ['\\|', ' '], trim((string)$v));
    $out = [];
    foreach ($rows as $i => $r) {
        $r = array_pad($r, $w, '');
        $out[] = '| ' . implode(' | ', array_map($cell, $r)) . ' |';
        if ($i === 0) {
            $out[] = '|' . str_repeat(' --- |', $w);
        }
    }
    return implode("\n", $out);
}

/** One file of a zip archive (stored or deflated), or null. */
function gen_zip_entry(string $zip, string $want): ?string
{
    $end = strrpos($zip, "PK\x05\x06");
    if ($end === false || strlen($zip) < $end + 22) {
        return null;
    }
    $e = unpack('vdisk/vstart/ventries/vtotal/Vsize/Voffset', substr($zip, $end + 4, 16));
    $p = $e['offset'];
    for ($i = 0; $i < $e['total'] && substr($zip, $p, 4) === "PK\x01\x02"; $i++) {
        $c = unpack('vmade/vneed/vflags/vmethod/vtime/vdate/Vcrc/Vcsize/Vusize/vnlen/velen/vclen/vdisk/viattr/Vattr/Vlocal', substr($zip, $p + 4, 42));
        $name = substr($zip, $p + 46, $c['nlen']);
        $p += 46 + $c['nlen'] + $c['elen'] + $c['clen'];
        if ($name !== $want) {
            continue;
        }
        $l = unpack('vnlen/velen', substr($zip, $c['local'] + 26, 4));
        $body = substr($zip, $c['local'] + 30 + $l['nlen'] + $l['elen'], $c['csize']);
        if ($c['method'] === 0) {
            return $body;
        }
        return $c['method'] === 8 && function_exists('gzinflate') ? (@gzinflate($body) ?: null) : null;
    }
    return null;
}

/** Text of a Word .docx: paragraphs as lines, table cells separated by |. */
function gen_pfile_docx(string $data, string $name): string
{
    $xml = gen_zip_entry($data, 'word/document.xml');
    if ($xml === null) {
        throw new InvalidArgumentException("$name: this is not a readable .docx file.");
    }
    // a cell's last paragraph ends the cell, not the line
    $xml = preg_replace(['#<w:tab/>#', '#<w:br[^>]*/>#', '#</w:p>\s*</w:tc>#', '#</w:tc>#', '#</w:p>#', '#</w:tr>#'], ["\t", "\n", ' | ', ' | ', "\n", "\n"], $xml);
    return gen_pfile_xml_text($xml);
}

/** Text of an OpenDocument .odt (LibreOffice, Google Docs export). */
function gen_pfile_odt(string $data, string $name): string
{
    $xml = gen_zip_entry($data, 'content.xml');
    if ($xml === null) {
        throw new InvalidArgumentException("$name: this is not a readable .odt file.");
    }
    $xml = preg_replace(['#<text:tab/>#', '#<text:s(?: [^>]*)?/>#', '#<text:line-break/>#', '#</text:(p|h)>\s*</table:table-cell>#', '#</table:table-cell>#', '#</text:(p|h)>#', '#</table:table-row>#'], ["\t", ' ', "\n", ' | ', ' | ', "\n", "\n"], $xml);
    return gen_pfile_xml_text($xml);
}

function gen_pfile_xml_text(string $xml): string
{
    $t = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
    return preg_replace('/ \| *\n/', "\n", $t); // the last cell of a row needs no separator
}

/** The brief part for the project's files: each file's text under its name, or ''. */
function gen_pfiles_brief(PDO $db, int $projectId): string
{
    gen_pfiles_table($db);
    $q = $db->prepare('SELECT name, kind, text FROM project_files WHERE project_id = ? ORDER BY id');
    $q->execute([$projectId]);
    $files = $q->fetchAll();
    if (!$files) {
        return '';
    }
    $L = ['### Project documents', ''];
    $L[] = '_Uploaded by the project owner. Use them as source material for facts, names, figures and wording; write new text from them rather than pasting them.'
        . ' Where a document conflicts with the market guidance, the market guidance wins._';
    foreach ($files as $f) {
        $L[] = '';
        $L[] = "#### {$f['name']}";
        $L[] = '';
        if ($f['kind'] === 'csv') {
            $L[] = $f['text'];
        } else {
            $L[] = '```text';
            $L[] = str_replace('```', "'''", $f['text']);
            $L[] = '```';
        }
    }
    return implode("\n", $L) . "\n";
}
