<?php
/*
Plugin Name: FAQ Plugin
Description: Add FAQs to your WordPress pages with schema markup for enhanced SEO.
Version: 1.0
Author: Fazal Khan
*/

function faq_meta_box() {
    add_meta_box(
        'faq-meta-box',
        'FAQs',
        'faq_meta_box_callback',
        'page',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'faq_meta_box');

// Meta box callback function
function faq_meta_box_callback($post) {
    
    $faqs = get_post_meta($post->ID, '_faq_data', true);
    ?>
    <div>
        <label for="faq_question">Question:</label><br>
        <input type="text" id="faq_question" name="faq_question"><br>
        <label for="faq_answer">Answer:</label><br>
        <textarea id="faq_answer" name="faq_answer" rows="4" cols="50"></textarea><br>
        <button type="button" id="add_faq">Add FAQ</button>
    </div>
    <div id="faq_list">
        <?php if ($faqs) : ?>
            <ul>
                <?php foreach ($faqs as $faq) : ?>
                    <li>
                        <strong><?php echo esc_html($faq['question']); ?></strong>: <?php echo esc_html($faq['answer']); ?>
                        <input type="hidden" name="faq_question[]" value="<?php echo esc_attr($faq['question']); ?>">
                        <input type="hidden" name="faq_answer[]" value="<?php echo esc_attr($faq['answer']); ?>">
                        <button type="button" class="remove_faq">Remove</button>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <script>
        jQuery(document).ready(function($) {
            $('#add_faq').click(function() {
                var question = $('#faq_question').val().trim();
                var answer = $('#faq_answer').val().trim();
                
                if (question && answer) {
                    var faqItem = '<li><strong>' + question + '</strong>: ' + answer;
                    faqItem += '<input type="hidden" name="faq_question[]" value="' + question + '">';
                    faqItem += '<input type="hidden" name="faq_answer[]" value="' + answer + '">';
                    faqItem += '<button type="button" class="remove_faq">Remove</button></li>';
                    
                    if ($('#faq_list ul').length == 0) {
                        $('#faq_list').append('<ul></ul>');
                    }
                    
                    $('#faq_list ul').append(faqItem);
                    
                    $('#faq_question').val('');
                    $('#faq_answer').val('');
                }
            });
            
            $(document).on('click', '.remove_faq', function() {
                $(this).parent().remove();
            });
        });
    </script>
    <?php
}

function save_faq_data($post_id) {
    if (isset($_POST['faq_question']) && isset($_POST['faq_answer'])) {
        $faq_questions = $_POST['faq_question'];
        $faq_answers = $_POST['faq_answer'];
        
        $faqs = [];
        foreach ($faq_questions as $key => $question) {
            $answer = isset($faq_answers[$key]) ? $faq_answers[$key] : '';
            if (!empty($question) && !empty($answer)) {
                $faqs[] = array(
                    'question' => sanitize_text_field($question),
                    'answer' => wp_kses_post($answer),
                );
            }
        }
        update_post_meta($post_id, '_faq_data', $faqs);
    } else {
        delete_post_meta($post_id, '_faq_data');
    }
}
add_action('save_post', 'save_faq_data');


// Function to display FAQs on the frontend
function display_faqs_frontend() {
    $faqs = get_post_meta(get_the_ID(), '_faq_data', true);
    if ($faqs) {
        echo '<div class="accordion-container">';
        echo '<script type="application/ld+json">';
        echo '{';
        echo '"@context": "https://schema.org",';
        echo '"@type": "FAQPage",';
        echo '"mainEntity": [';
        foreach ($faqs as $index => $faq) {
            $accordion_title = esc_html($faq['question']);
            $accordion_content = wpautop(esc_html($faq['answer'])); 
            echo '{';
            echo '"@type": "Question",';
            echo '"name": "' . $accordion_title . '",';
            echo '"acceptedAnswer": {';
            echo '"@type": "Answer",';
            echo '"text": "' . $accordion_content . '"';
            echo '}';
            if ($index < count($faqs) - 1) {
                echo '},';
            } else {
                echo '}';
            }
        }
        echo ']';
        echo '}';
        echo '</script>';
        foreach ($faqs as $index => $faq) {
            $accordion_title = esc_html($faq['question']);
            $accordion_content = wpautop(esc_html($faq['answer'])); 
            echo '<div class="accordion">';
            echo '<input type="checkbox" id="accordion-' . $index . '" class="accordion-checkbox">';
            echo '<label for="accordion-' . $index . '" class="accordion-header">' . $accordion_title . '</label>';
            echo '<div class="accordion-content">' . $accordion_content . '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
}
add_shortcode('display_faqs', 'display_faqs_frontend');



// Enqueue scripts and styles
function faq_enqueue_scripts() {
    wp_enqueue_style('faq-accordion-style', plugin_dir_url(__FILE__) . 'accordion.css');
}
add_action('wp_enqueue_scripts', 'faq_enqueue_scripts');
function faq_enqueue_script_footer() {
    ?>
    <script src="<?php echo plugin_dir_url(__FILE__) . 'accordion.js'; ?>" defer></script>
    <?php
}
add_action('wp_footer', 'faq_enqueue_script_footer');


