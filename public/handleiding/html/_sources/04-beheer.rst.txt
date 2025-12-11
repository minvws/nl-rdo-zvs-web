.. _Beheer:

Beheer
======

*Snelkoppelingen:*

- `Tweefactorauthenticatie reset`_
- `Gebruikersrechten`_

.. _gebruikersaccount:

Gebruikersaccount
-----------------

Nieuwe gebruiker aanmaken
^^^^^^^^^^^^^^^^^^^^^^^^^
Als applicatiebeheerder kan een nieuwe gebruiker worden toegevoegd via het menu **Gebruikers**.
Hierbij kan per team het gewenste toegangsrecht worden ingesteld, zoals schrijfrechten voor Team C, leesrechten voor Team A en B, en geen rechten voor Team WJZ.
Na opslaan is de gebruiker aangemaakt.

.. _Gebruikersrechten:

Gebruikersrechten
^^^^^^^^^^^^^^^^^

U kunt gebruikers lees- of schrijfrechten geven binnen alle organisaties.
Indien er geen rechten zijn toegekend in een organisatie zal deze organisatie voor die gebruiker ook niet te zien zijn in het menu linksbovenin.

Leesrechten
"""""""""""

Indien u Leesrechten in een organisatie toekent aan een gebruiker, dan mag deze gebruiker:

- Alle :ref:`Zaken` van de organisatie **inzien**
- Alle :ref:`Besluiten` van de organisatie **inzien**
- Alle :ref:`Contacten` van de organisatie **inzien**

Schrijfrechten
""""""""""""""

**Let op**: Als u schrijfrechten toekent aan een gebruiker, dan kent u automatisch ook leesrechten toe!

- Alle :ref:`Zaken` van de organisatie **inzien en bewerken**
- Alle :ref:`Besluiten` van de organisatie **inzien en bewerken**
- Alle :ref:`Contacten` van de organisatie **inzien en bewerken**

Bestaande gebruiker aanpassen
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
Een bestaande gebruiker kan worden gewijzigd door de naam en/of het e-mailadres aan te passen en de rechten te bewerken.
Na het opslaan zijn de nieuwe gegevens zichtbaar in het systeem en heeft de gebruiker direct nieuwe rechten.

.. _Tweefactorauthenticatie reset:

Tweefactorauthenticatie reset
"""""""""""""""""""""""""""""

*Sleutelwoorden: 2FA, Tweefactorauthenticatie code, One Time Password protection, Microsoft Authenticator App, Google Authenticator App, Nieuwe telefoon*

Indien een gebruiker geen toegang meer heeft tot het Zaakvolgsysteem omdat de gebruiker geen geactiveerde authenticator app heeft, volg dan deze stappen:

1. Onder Beheer > Gebruikers, vindt de gebruiker waar u de toegang wil herstellen: klik op zijn of haar naam.
2. Onderaan het formulier van de gebruiker, klik op de knop "Tweefactorauthenticatie herstellen".
3. Vraag de gebruiker om opnieuw in te loggen.
4. De gebruiker ziet na het inloggen een scherm Profiel met Tweefactorauthenticatie en een knop "Inschakelen": vraag hun daarop te klikken.
5. De gebruiker kan met :ref:`een authenticator applicatie` de QR code scannen: er wordt een 6-cijferige code getoond.
6. De gebruiker dient deze code in te voeren en op te slaan.
7. De gebruiker heeft weer toegang tot het hele systeem en kan in het menu op :ref:`Zaken` klikken.

Zaaksoortenbeheer
-----------------

Zaaksoort aanmaken
^^^^^^^^^^^^^^^^^^
Een nieuwe zaaksoort kan worden toegevoegd via het menu **Zaaksoorten**.
Na het invullen van de velden en het opslaan verschijnt de zaaksoort boven de :ref:`Zaken` tabel.

Bestaande zaaksoort aanpassen
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
Een bestaande zaaksoort kan worden gewijzigd door de naam en het bijzonderheidslabel te bewerken.
Tevens kan een zaaksoort op inactief worden gezet: dit verwijdert de knop boven de :ref:`Zaken` tabel en zo kunnen er geen nieuwe :ref:`zaken` worden aangemaakt met dit type.
Na opslaan worden de wijzigingen zichtbaar in het overzicht van zaaksoorten.

**Let op**: Het Zaaktype kan niet worden gewijzigd!
Dit zou voor bestaande :ref:`zaken` met dit type direct issues opleveren gegeven de status en eigenschappen.
Mocht er sprake zijn van een incorrect zaaktype graag de incorrecte zaaksoort deactiveren en een nieuwe zaaksoort aanmaken met de juiste zaaksoort.

Categoriebeheer
---------------

Nieuwe categorie aanmaken
^^^^^^^^^^^^^^^^^^^^^^^^^
Via het menu **Categorieën** kan een nieuwe categorie worden toegevoegd.
Na het invoeren van de naam en het opslaan verschijnt de nieuwe categorie in het overzicht.

Bestaande categorie aanpassen
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
Een bestaande categorie kan worden gewijzigd door de naam te bewerken en op te slaan.
Tevens kan een categorie op inactief worden gezet: dit verwijdert de optie in het aanmaakformulier en de categoriefilter.
Na opslaan is de wijziging direct zichtbaar in het overzicht van categorieën.
