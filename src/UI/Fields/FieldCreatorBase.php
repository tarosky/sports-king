<?php

namespace Tarosky\Common\UI\Fields;


trait FieldCreatorBase {


    protected $positions = [
        '1'  => 'C',
        '2'  => 'PF',
        '3'  => 'SF',
        '4'  => 'SG',
        '5'  => 'PG',
    ];

    /**
     * Override parent's method.
     */
    public function display_field() {
        $current_value = $this->get_data( false );
        ?>
        <div class="soccerField">
            <?php /*<img src="<?= get_template_directory_uri() ?>/assets/images/admin/bg-field.png" class="soccerField__image" />*/?>
        <table class="soccerField__table">
            <?php
            foreach( [
                'guard' => [
                    [
                        '4' => 1,
                        '5' => 1,
                    ],
                ],
                'forward' => [
                    [
                        '2' => 1,
                        '3'  => 1,
                    ],
                ],
                'center' => [
                    [
                        '1' => 2,
                    ],
                ]
            ] as $row => $cells){
                foreach( $cells as $lines ){
                    $name = $this->field['name'];
                    ?>
                    <tr class="soccerField__<?= $row ?>">
                        <?php foreach( $lines as $pos => $col_span ) : ?>
                            <td class="soccerField__cell" colspan="<?= $col_span ?>">
                                <input class="soccerField__input" type="<?= $this->type; ?>" name="<?= $this->get_name() ?>" id="<?= $name.'-'.$pos ?>"
                                value="<?= esc_html( $pos ) ?>
                                " <?php checked( $this->checked( $pos, $current_value ) ) ?> />
                                <label class="soccerField__label" for="<?= $name.'-'.$pos ?>">
                                    <span class="soccerField__string"><?= esc_html($this->positions[$pos]) ?></span>
                                </label>
                            </td>
                        <?php endforeach; ?>
                    </tr >
                    <?php

                }
            }
            ?>
        </table>
        </div>
        <?php
    }
}
