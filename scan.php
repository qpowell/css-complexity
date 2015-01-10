---
layout: default
---
<?php
  include ("lib/functions.php");
  $url = '';
  $total = 0;
  $total_size = 0;
  $unused_size = 0;
  $i = 0;
  $content = '';
  $matches = array();
  $doc = new DOMDocument();
  $tests = array(
    'h1',
    'h2',
    'h3',
    'h4',
    'h5',
    'h6',
    'margin',
    'padding',
    'margin',
    'padding: 0',
    'margin: 0',
    'font',
    'font-size',
    'font-family',
    '!important',
    'color',
    'hex',
    '#fff',
    'background',
    );

  if (isset($_GET['url']) && !empty($_GET['url'])) {
    $url = strip_tags($_GET['url']);

    // TODO: Improve test for valid domain.
    $url = wash_url($url);

    // Load the remote file into a local document.Then extract all the CSS files
    // TODO: add support for CSS Import and inline styles
    libxml_use_internal_errors(true);
    $doc->loadHTML(get_page($url));
    $css_files = $doc->getElementsByTagName('link');
    foreach ($css_files as $css_file) {
      if (strtolower($css_file->getAttribute('rel')) == "stylesheet") {
        $i++;

        // Remove any questions
        $file_name = explode("?", $css_file->getAttribute('href'), 2);

        // Add base URL if the path is relative
        $pos = strpos($file_name[0], 'http');
        if ($pos === false) {
          $parse = parse_url($url);
          if (!empty($parse['path'])) {
            $path_parts = pathinfo($parse['path']);
            if (!empty($path_parts['dirname'])) {
              $file_name[0] = $parse['host'] . '/' . $path_parts['dirname'] . '/' . $file_name[0];
            }
          }
          else {
            $file_name[0] = $parse['host'] . '/' . $file_name[0];
          }
        }
        // print('CSS File: ' . $file_name[0] . '<br />');

        // Create a single stylesheet to make scoring faster to code.
        // This approach my break on huge sites. Maybe I should ajax 1 file at a time incrementally testing them.
        $content .= get_page($file_name[0]);
      }
    }
    if (empty($content)) {
      //header('Location: /?error=content');
    }

    // Some stack overflow code to tally the results.
    foreach ($tests as $test) {
      $matches[$test] = $count = substr_count($content, $test);
      $total += $count;
    }

    // Calculate unused CSS
    $total_size = get_size($content);
    $used_size = get_size(shell_exec("/usr/bin/uncss $url"));
    $unused_size = $total_size - $used_size;

    // Convert to KB
    $unused_size = number_format($unused_size / 1024, 2);
    $total_size = number_format($total_size / 1024, 2);
  }
?>

<div class="wrap">
  <a href="/">New Scan</a> | <a href="/scan.php?url=<?php echo $url; ?>">Rescan</a>
  <fieldset class="results">
    <legend>Found <?php echo $i; ?> files on <?php echo $url; ?></legend>
    <table class="score">
      <tr>
        <th>Score</th>
        <th>Files</th>
        <th>Size</th>
      </tr>
      <tr>
        <td><?php echo $total; ?></td>
        <td><?php echo $i; ?></td>
        <td><?php echo $total_size; ?> KB</td>
      </tr>
    </table>
    <table class="body">
      <tr class="title">
        <th>Selector</th>
        <th>Count</th>
      </tr>
      <?php foreach ($matches as $key => $match) { ?>
        <tr>
          <td class="key"><?php echo $key; ?></td>
          <td class="match"><?php echo $match; ?></td>
        </tr>
      <?php
        }
      ?>
    </table>
  </fieldset>
</div>
