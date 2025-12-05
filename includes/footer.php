<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<?php
// Footer: multi-kolom layout, contact info, nieuwsbrief, taalafhankelijke labels
// - Haalt taal uit sessie (standaard NL)
// - $L array bevat NL en EN labels voor alle footer onderdelen
// - Kolommen: Plan bezoek, Onderzoek, Over ons, Contact, Nieuwsbrief
// - Contact info: telefoon, email, adres, chat, social media links
// - Nieuwsbrief: email signup formulier
// - Footer bottom: copyright en bedrijfsgegevens (IBAN, KvK, BTW)
$lang = $_SESSION['lang'] ?? 'nl';
$L = [
  'nl' => [
    'visit' => 'Plan een bezoek',
    'expo' => 'Expo - Hamburgerstraat 28',
    'study' => 'Studiezaal - Alexander Numankade 199 - 201',
    'research' => 'Onderzoek',
    'search_archives' => 'Archieven doorzoeken',
    'view_images' => 'Beeldmateriaal bekijken',
    'blueprints' => 'Bouwtekeningen',
    'search_people' => 'Personen zoeken',
    'about' => 'Over ons',
    'news' => 'Nieuws',
    'agenda' => 'Agenda',
    'contribute' => 'Uw materiaal in ons archief',
    'contact' => 'Contact',
    'accessibility' => 'Toegankelijkheid',
    'copyright' => 'Auteursrecht en disclaimer',
    'privacy' => 'Privacyverklaring',
    'anbi' => 'ANBI',
    'english' => 'English',
    'colophon' => 'Colofon',
    'contactTitle' => 'Contact',
    'phone' => '☎️ (030) 286 66 11',
    'email_label' => '✉️',
    'post' => '📮 Postadres: Postbus 131, 3500 AC Utrecht',
    'chat' => '💬 Chat: di t/m do 9.00 - 13.00 uur',
    'newsletter' => 'Blijf op de hoogte van het laatste nieuws',
    'email_ph' => 'E-mailadres',
    'send' => 'Verstuur',
    'meta' => 'IBAN: NL66RABO0123881641<br>KvK: 62047302<br>BTW: NL807024594B01',
    'copyright_footer' => '&copy; Het Utrechts Archief',
    'ask' => 'Stel een vraag'
  ],
  'en' => [
    'visit' => 'Plan a visit',
    'expo' => 'Exhibition - Hamburgerstraat 28',
    'study' => 'Study room - Alexander Numankade 199 - 201',
    'research' => 'Research',
    'search_archives' => 'Search archives',
    'view_images' => 'View images',
    'blueprints' => 'Blueprints',
    'search_people' => 'Search people',
    'about' => 'About us',
    'news' => 'News',
    'agenda' => 'Agenda',
    'contribute' => 'Contribute your material',
    'contact' => 'Contact',
    'accessibility' => 'Accessibility',
    'copyright' => 'Copyright and disclaimer',
    'privacy' => 'Privacy statement',
    'anbi' => 'Public Benefit (ANBI)',
    'english' => 'English',
    'colophon' => 'Colophon',
    'contactTitle' => 'Contact',
    'phone' => '☎️ (030) 286 66 11',
    'email_label' => '✉️',
    'post' => '📮 Postal address: Postbus 131, 3500 AC Utrecht',
    'chat' => '💬 Chat: Tue–Thu 9:00–13:00',
    'newsletter' => 'Stay up to date with the latest news',
    'email_ph' => 'Email address',
    'send' => 'Send',
    'meta' => 'IBAN: NL66RABO0123881641<br>CoC: 62047302<br>VAT: NL807024594B01',
    'copyright_footer' => '&copy; Utrecht Archives',
    'ask' => 'Ask a question'
  ]
][$lang];
?>
<footer class="site-footer" aria-label="Voettekst">
        <div class="footer-columns">
            <div class="footer-col" aria-label="<?php echo htmlspecialchars($L['visit']); ?>">
                <h3><?php echo htmlspecialchars($L['visit']); ?></h3>
                <ul>
                    <li><a href="#"><?php echo htmlspecialchars($L['expo']); ?></a></li>
                    <li><a href="#"><?php echo htmlspecialchars($L['study']); ?></a></li>
                </ul>
            </div>
            <div class="footer-col" aria-label="<?php echo htmlspecialchars($L['research']); ?>">
                <h3><?php echo htmlspecialchars($L['research']); ?></h3>
                <ul>
                    <li><a href="#"><?php echo htmlspecialchars($L['search_archives']); ?></a></li>
                    <li><a href="#"><?php echo htmlspecialchars($L['view_images']); ?></a></li>
                    <li><a href="#"><?php echo htmlspecialchars($L['blueprints']); ?></a></li>
                    <li><a href="#"><?php echo htmlspecialchars($L['search_people']); ?></a></li>
                </ul>
            </div>
            <div class="footer-col" aria-label="<?php echo htmlspecialchars($L['about']); ?>">
                <h3><?php echo htmlspecialchars($L['about']); ?></h3>
                <ul>
                    <li><a href="#"><?php echo htmlspecialchars($L['news']); ?></a></li>
                    <li><a href="#"><?php echo htmlspecialchars($L['agenda']); ?></a></li>
                    <li><a href="#"><?php echo htmlspecialchars($L['contribute']); ?></a></li>
                    <li><a href="#"><?php echo htmlspecialchars($L['contact']); ?></a></li>
                    <li><a href="#"><?php echo htmlspecialchars($L['accessibility']); ?></a></li>
                    <li><a href="#"><?php echo htmlspecialchars($L['copyright']); ?></a></li>
                    <li><a href="#"><?php echo htmlspecialchars($L['privacy']); ?></a></li>
                    <li><a href="#"><?php echo htmlspecialchars($L['anbi']); ?></a></li>
                    <li><a href="#"><?php echo htmlspecialchars($L['english']); ?></a></li>
                    <li><a href="colofon.php"><?php echo htmlspecialchars($L['colophon']); ?></a></li>

                </ul>
            </div>
            <div class="footer-col" aria-label="<?php echo htmlspecialchars($L['contactTitle']); ?>">
                <h3><?php echo htmlspecialchars($L['contactTitle']); ?></h3>
                <ul class="contact-list">
                    <li><?php echo htmlspecialchars($L['phone']); ?></li>
                    <li><?php echo htmlspecialchars($L['email_label']); ?> <a href="mailto:inlichtingen@hetutrechtsarchief.nl">inlichtingen@hetutrechtsarchief.nl</a></li>
                    <li><?php echo htmlspecialchars($L['post']); ?></li>
                    <li><?php echo htmlspecialchars($L['chat']); ?></li>
                </ul>
                <div class="social" aria-label="Social media">
                    <a href="#" aria-label="Facebook">f</a>
                    <a href="#" aria-label="Instagram">ig</a>
                    <a href="#" aria-label="YouTube">yt</a>
                    <a href="#" aria-label="RSS">rss</a>
                </div>
            </div>
            <div class="footer-col newsletter" aria-label="Nieuwsbrief">
                <h3><?php echo htmlspecialchars($L['newsletter']); ?></h3>
                <form action="#" method="post" class="newsletter-form">
                    <label for="email" class="visually-hidden"><?php echo htmlspecialchars($L['email_ph']); ?></label>
                    <input id="email" name="email" type="email" placeholder="<?php echo htmlspecialchars($L['email_ph']); ?>" required />
                    <button type="submit"><?php echo htmlspecialchars($L['send']); ?></button>
                </form>
                <div class="meta" aria-label="Bedrijfsgegevens">
                    <p><?php echo $L['meta']; ?></p>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p><?php echo $L['copyright_footer']; ?></p>
        </div>
        <button type="button" class="chat-button" aria-label="<?php echo htmlspecialchars($L['ask']); ?>"><?php echo htmlspecialchars($L['ask']); ?></button>
    </footer>