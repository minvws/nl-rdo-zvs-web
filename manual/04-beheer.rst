.. _Beheer:

Beheer
======

*Snelkoppelingen:*

- `Tweefactorauthenticatie reset`_
- `Gebruikersrechten`_

Algemeen beheer
---------------
*Onder de kop "Algemeen"*.

.. _Gebruikers:

Gebruikersbeheer
^^^^^^^^^^^^^^^^
Met de knop **Gebruikers** in het menu kunnen gebruikers worden beheerd.
Op deze pagina is een overzicht van alle gebruikers binnen de organisatie te zien.
Hier kunnen nieuwe gebruikers worden toegevoegd, bestaande gebruikers worden aangepast en tweefactorauthenticatie worden gereset.

Nieuwe gebruiker aanmaken
"""""""""""""""""""""""""
Als applicatiebeheerder kan een nieuwe gebruiker worden toegevoegd door op **Aanmaken** te klikken rechtsboven de gebruikerstabel.
Hierbij kan per team het gewenste toegangsrecht worden ingesteld, zoals schrijfrechten voor Team C, leesrechten voor Team A en B, en geen rechten voor Team WJZ.
Na opslaan is de gebruiker aangemaakt.

Bestaande gebruiker aanpassen
"""""""""""""""""""""""""""""
Een bestaande gebruiker kan worden gewijzigd door de naam en/of het e-mailadres aan te passen en de rechten te bewerken.
Na het opslaan zijn de nieuwe gegevens zichtbaar in het systeem en heeft de gebruiker direct nieuwe rechten.

.. _Gebruikersrechten:

Gebruikersrechten
"""""""""""""""""
U kunt gebruikers lees- of schrijfrechten geven binnen alle afdelingen.
Indien er geen rechten zijn toegekend in een afdeling zal deze afdeling voor die gebruiker ook niet te zien zijn in het menu linksbovenin.

Leesrechten
+++++++++++
Indien u Leesrechten in een afdeling toekent aan een gebruiker, dan mag deze gebruiker:

- Alle :ref:`Zaken` van de afdeling **inzien**
- Alle :ref:`Besluiten` van de afdeling **inzien**
- Alle :ref:`Contacten` van de afdeling **inzien**

Schrijfrechten
+++++++++++
**Let op**: Als u schrijfrechten toekent aan een gebruiker, dan kent u automatisch ook leesrechten toe!

- Alle :ref:`Zaken` van de afdeling **inzien en bewerken**
- Alle :ref:`Besluiten` van de afdeling **inzien en bewerken**
- Alle :ref:`Contacten` van de afdeling **inzien en bewerken**

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

Beleidsdirectiebeheer
^^^^^^^^^^^^^^^^^^^^^
Via de knop **Beleidsdirecties** in het menu kunnen beleidsdirecties worden beheerd.
Op deze pagina is een overzicht van alle beleidsdirecties binnen de organisatie te zien.
Hier kunnen nieuwe beleidsdirecties worden toegevoegd en bestaande beleidsdirecties worden aangepast.

Nieuwe beleidsdirectie aanmaken
"""""""""""""""""""""""""""""""
Via de knop **Beleidsdirectie toevoegen** rechtsboven de tabel kan een nieuwe beleidsdirectie worden aangemaakt.

Bestaande beleidsdirectie aanpassen
"""""""""""""""""""""""""""""""""""
Een bestaande beleidsdirectie kan worden gewijzigd door de naam te bewerken en op te slaan.
Een beleidsdirectie kan ook op inactief worden gezet.

Feestdagenbeheer
^^^^^^^^^^^^^^^^

Nieuwe feestdag aanmaken
""""""""""""""""""""""""
Via het menu **Feestdagen** kunnen feestdagen worden beheerd die van invloed zijn op de termijnberekening.
Voer de datum en naam van de feestdag in en sla op.

Bestaande feestdag aanpassen of verwijderen
"""""""""""""""""""""""""""""""""""""""""""
Een bestaande feestdag kan worden gewijzigd of verwijderd.
Verwijdering heeft direct effect op de termijnberekeningen die van die datum afhankelijk zijn.

Afdelingsbeheer
---------------
Ook vind u alle afdelingen onder de kop *Afdelingen* met hieronder de beheeropties per afdeling.

Zaaksoortenbeheer
^^^^^^^^^^^^^^^^^
*Via de knop **Zaaksoorten**.*

Zaaksoort aanmaken
""""""""""""""""""
Een nieuwe zaaksoort kan worden toegevoegd door op de knop **Zaaksoort toevoegen** rechtsboven de tabel te klikken.
Na het invullen van de velden en het opslaan verschijnt de zaaksoort boven de :ref:`Zaken` tabel.

Bestaande zaaksoort aanpassen
"""""""""""""""""""""""""""""
Een bestaande zaaksoort kan worden gewijzigd door de naam en het bijzonderheidslabel te bewerken.
Tevens kan een zaaksoort op inactief worden gezet: dit verwijdert de knop boven de :ref:`Zaken` tabel en zo kunnen er geen nieuwe :ref:`zaken` worden aangemaakt met dit type.
Na opslaan worden de wijzigingen zichtbaar in het overzicht van zaaksoorten.

**Let op**: Het Zaaktype kan niet worden gewijzigd!
Dit zou voor bestaande :ref:`zaken` met dit type direct issues opleveren gegeven de status en eigenschappen.
Mocht er sprake zijn van een incorrect zaaktype graag de incorrecte zaaksoort deactiveren en een nieuwe zaaksoort aanmaken met de juiste zaaksoort.

Categoriebeheer
^^^^^^^^^^^^^^^
*Via de knop **Categorieën**.*

Nieuwe categorie aanmaken
"""""""""""""""""""""""""
Een nieuwe categorie kan worden toegevoegd door op de knop **Categorie toevoegen** rechtsboven de tabel te klikken.
Na het invoeren van de naam en het opslaan verschijnt de nieuwe categorie in het overzicht.

Bestaande categorie aanpassen
"""""""""""""""""""""""""""""
Een bestaande categorie kan worden gewijzigd door de naam te bewerken en op te slaan.
Tevens kan een categorie op inactief worden gezet: dit verwijdert de optie in het aanmaakformulier en de categoriefilter.
Na opslaan is de wijziging direct zichtbaar in het overzicht van categorieën.

Teamsbeheer
^^^^^^^^^^^
*Via de knop **Teams**.*

Nieuw team aanmaken
"""""""""""""""""""
Een nieuw team kan worden toegevoegd door op de knop **Team toevoegen** rechtsboven de tabel te klikken.
Na het invoeren van de naam en het opslaan verschijnt het team in het overzicht en is het beschikbaar als teamkeuze bij het aanmaken van zaken.

Bestaand team aanpassen
"""""""""""""""""""""""
Een bestaand team kan worden gewijzigd door de naam te bewerken.
Tevens kan een team op inactief worden gezet, waarna het niet langer beschikbaar is als keuze bij nieuwe zaken.
Na opslaan is de wijziging direct zichtbaar in het teamoverzicht.
