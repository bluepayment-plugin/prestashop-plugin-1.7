# Moduł Płatności BlueMedia dla platformy PrestaShop 1.7

## Podstawowe informacje
BluePayment to moduł płatności umożliwiający realizację transakcji bezgotówkowych w sklepie opartym na platformie PrestaShop 1.7.

### Główne funkcje
Do najważniejszych funkcji modułu zalicza się:
- realizację płatności online poprzez odpowiednie zbudowanie startu transakcji
- obsługę powiadomień o statusie transakcji (notyfikacje XML)
- obsługę wielu sklepów jednocześnie z użyciem jednego modułu
- obsługę zakupów bez rejestracji w serwisie
- obsługę dwóch trybów działania – testowego i produkcyjnego (dla każdego z nich wymagane są osobne dane kont, po które zwróć się do nas)
- wybór banku po stronie sklepu i bezpośrednie przekierowanie do płatności w wybranym banku

### Wymagania
- Wersja PrestaShop: 1.7.0.0 - 1.7.7.6
- Wersja PHP zgodna z wymaganiami względem danej wersji sklepu minimum php 7.0

## Instalacja wtyczki

1) Pobierz najnowszą wersję wtyczki klikając [tutaj](https://github.com/bluepayment-plugin/prestashop-plugin-1.7/archive/refs/heads/master.zip).

2) Wejdź na http(s)://domena_sklepu.pl/nazwa_katalogu_administratora i zaloguj się do swojego konta administratora używając loginu i hasła.

![Logowanie](https://user-images.githubusercontent.com/87177993/130193963-b91d3de8-57a6-4c62-80e3-64a2ce93d735.png)

3) Po zalogowaniu się przejdź do zakładki **Moduły > Moduły i usługi** i:
- kliknij **Dodaj nowy moduł** (widoczny w prawym górnym rogu), by wgrać paczkę plików, którą pobrałeś w poprzednim kroku;

![Dodaj nowy moduł](https://user-images.githubusercontent.com/87177993/130194405-94fe13b7-e2f8-4ea9-bea0-8db12ebeeb9e.png)

*(Po kliknięciu przycisku pojawi się okno umożliwiające wybór pliku z komputera.)*

● kliknij **Załaduj moduł**

Gdy instalacje się zakończy, system przeniesie cię automatycznie do Konfiguracji modułu.

![Konfiguracja](https://user-images.githubusercontent.com/87177993/130194624-c8371417-f31d-44ea-ba0b-872e878de014.png)

## Konfiguracja

### Konfiguracja sklepu

1) Zaloguj się za pomocą konta administratora na adres:
   http(s)://domena_sklepu.pl/nazwa_katalogu_administratora

![Logowanie](https://user-images.githubusercontent.com/87177993/130193963-b91d3de8-57a6-4c62-80e3-64a2ce93d735.png)

2) Przejdź do zakładki **Preferencje ➝ Ruch**, znajdź **Przyjazny adres URL** i włącz klikając **Tak**.

![Przyjazny adres URL](https://user-images.githubusercontent.com/87177993/130194986-893c69f6-e47c-428d-884e-44e8ffcfc6e6.png)


### Konfiguracja modułu

1) Przejdź do zakładki **Moduły ➝ Moduły i usługi** i wybierz z listy modułów kategorię: **Płatności, bramki, operatorzy** (lub wyszukaj moduł za pomocą wyszukiwarki).

2) Wybierz **Konfiguruj Płatności online BM** i uzupełnij wszystkie dane (otrzymasz je od nas). Jeżeli przycisk **Konfiguruj** nie jest widoczny – należy ponownie zainstalować moduł.

3) Żeby uzyskać od nas Identyfikator serwisu partnera oraz Klucz współdzielony – prześlij do nas adresy do komunikacji między sklepem a bramką płatniczą:
- http(s)://domena_sklepu.pl/module/bluepayment/back
- http(s)://domena_sklepu.pl/module/bluepayment/status

![Ustawienia](https://user-images.githubusercontent.com/87177993/130194624-c8371417-f31d-44ea-ba0b-872e878de014.png)

Opis pól:
1. Tryb testowy – zmiana trybu pracy bramki na testowy umożliwia weryfikację działania modułu bez konieczności rzeczywistego opłacania zamówienie (w trybie testowym nie pobierane są żadne opłaty za zamówienie).
2. Pokazuj kanały płatności w sklepie – po wybraniu płatności za pomocą Blue Media prezentowane są możliwe kanały płatności (banki), dzięki czemu użytkownik może wybrać bank już na poziomie sklepu.
3. Pokazuj logo kanałów płatności – przy nazwach banków wyświetlane są ich logotypy.
4. Identyfikator serwisu partnera – składa się tylko z cyfr i jest inny dla każdego sklepu (uzyskasz go od Blue Media jest).
5. Klucz współdzielony – służy do weryfikacji komunikacji w bramką płatności. Zawiera cyfry i małe litery. Nie należy go udostępniać publicznie (uzyskasz go od Blue Media).
6. Status oczekiwania na płatność – status zamówienia w sklepie – ustawiany natychmiast po rozpoczęciu płatności.
7. Status prawidłowej odpowiedzi – status zamówienia w sklepie – ustawiany po potwierdzeniu płatności.
8. Status nieprawidłowej płatności – status ustawiany w przypadku niepowodzenia płatności lub gdy płatności nie została zrealizowana przez dłuży czas (czas ten ustalamy dla każdego sklepu indywidualnie).
9. Nazwa metody płatności – umożliwia zmianę nazwy metody płatności, prosimy o pozostawienie w tym miejscu słów „Blue Media”.
10. Dodatkowy opis przy nazwie metody płatności – wyświetlany przy nazwie płatności na stronach koszyka, pole możesz wykorzystać do wyjaśnienie zasady działania płatności z wykorzystaniem modułu Blue Media.

Po uzupełnieniu wszystkich pól – kliknij **Zapisz**.

## Zarządzanie kanałami płatności

1) Zaloguj się za pomocą konta administratora na adres:
   http(s)://domena_sklepu.pl/nazwa_katalogu_administratora
   
2) Przejdź do zakładki **Moduły ➝ Menadżer modułów ➝ Płatności online BM ➝ Konfiguruj**

3) Żeby pobrać kanały płatności, kliknij **Odśwież** – po pobraniu powinna się pojawić lista kanałów płatności dla wybranego trybu pracy (testowy/produkcyjny).

Jeżeli podczas pobierania pojawi się błąd – najprawdopodobniej podczas konfiguracji modułu zostały podane nieprawidłowe dane (Klucz współdzielony lub Identyfikator serwisu partnera)

💡 Panel umożliwia również dezaktywowanie/aktywowanie kanału płatności z poziomu sklepu.

### Logi

W przypadku pojawienia się błędów podczas przetwarzania transakcji zapisywana jest odpowiednia informacja, która ma pomóc w szybszym odnalezieniu przyczyny problemu.

Żeby przejrzeć logi – przejdź do zakładki **Zaawansowane > Logi** i uzupełnij następujące filtry:
- Wiadomość BLUEPAYMENT

### Zamówienia

W podglądzie zamówienia, w sekcji **Zamówienie** dodawane są wpisy związane z informacjami na temat przebiegu procesu transakcji.

### Transakcje i faktury

Tworzone są automatycznie w zależności od ustawień statusów transakcji.

### Powiadomienia mailowe

Powiadomienia o zmianie statusu płatności wysyłane są w zależności od konfiguracji danego statusu. Jeżeli chcesz żeby powiadomienia były wysyłane – zaznacz opcję **Wyślij email do klienta, kiedy zmieni się status zamówienia** (wybrany musi być również odpowiedni szablon).

## Aktualizacja

1) Żeby dokonać aktualizacji – Wystarczy kliknąć Upgrade.

![Upgrade](https://user-images.githubusercontent.com/87177993/130195194-14d14c9a-1cfa-43f8-aa4b-c82e72a28dac.png)

2) Następnie postępuj zgodnie z instrukcją opisaną w sekcji **Instalacja wtyczki**.

## Odinstalowanie
Żeby odinstalować moduł – wybierz **Odinstaluj**.

![Odinstaluj](https://user-images.githubusercontent.com/87177993/130195263-931b9ac8-a2f0-42c1-841f-0fc853a716b4.png)
