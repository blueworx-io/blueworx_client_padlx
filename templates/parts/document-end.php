<?php
/**
 * Footer and closing tags.
 */

namespace BlueWorx\PadlX;

defined( 'ABSPATH' ) || exit;
?>
	<footer class="bwpx-footer">
		<img src="<?php echo esc_url( asset( 'logo.svg' ) ); ?>" width="156" height="31" alt="PADLX">
		<p class="bwpx-lede">For the love of the game.</p>
	</footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
