# 🛒 Vending Machine - Ecossistema Completo IoT

Bem-vindo ao repositório central do projeto **Fibrag Vending Machine**. Este projeto é uma solução de Internet das Coisas (IoT) ponta a ponta que engloba um sistema web de e-commerce, processamento de pagamentos, firmware para microcontroladores, gateways de roteamento de dados e simuladores digitais.

A arquitetura foi desenhada para ser altamente distribuída, assíncrona e resiliente, utilizando múltiplos protocolos (HTTP REST, MQTT) e plataformas de nuvem.



---

## 🏗️ Arquitetura do Sistema (Fluxo de Dados)

O funcionamento da Fibrag Vending Machine segue um fluxo estruturado em 4 pilares:

1. **Compra e Pagamento:** O cliente acessa a interface web, gera um PIX e paga. O Backend (PHP) valida o status.
2. **Comando de Liberação:** O Admin Web envia um comando via **API REST (HTTP GET)** para o broker nuvem do **ThingSpeak**.
3. **Atuação Física/Virtual:** O hardware (ESP32) e o Simulador (Tkinter), ambos inscritos no **ThingSpeak via MQTT**, recebem o comando instantaneamente e liberam o produto.
4. **Telemetria e Dashboards:** O ESP32 publica status ambientais e confirmações de vendas no **HiveMQ (MQTT)**. Um Gateway em Python captura esses dados e roteia para o **Ubidots** e **ThingSpeak** para visualização em dashboards.

---

## 🖼️ Visão Geral Visual do Projeto


<img width="821" height="464" alt="Captura de tela de 2026-02-21 15-52-52" src="https://github.com/user-attachments/assets/146917d7-b3be-4ad4-a5a1-8b7833f5b9df" />


---

## 🌐 Dashboards e Simulação em Nuvem

Acompanhe o funcionamento da **Fibrag Vending Machine** em tempo real através dos links abaixo:

* 🛠️ **[Simulação do Hardware no Wokwi](https://wokwi.com/projects/456531482103047169)**
  * *Acesse o gêmeo digital do circuito físico do ESP32 operando diretamente no navegador.*

* 📊 **[Dashboard de Telemetria no Ubidots](https://stem.ubidots.com/app/dashboards/public/dashboard/JOoCwNYp5KrRVVs8Qy8G1-4HIjhEPiMy?navbar=true&contextbar=false&layersBar=false)**
  * *Visualize os gráficos em tempo real de temperatura, umidade, estoque e eventos de movimento.*

* ☁️ **[Canal de Controle no ThingSpeak](https://thingspeak.mathworks.com/channels/3267232)**
  * *Acompanhe os logs brutos da API REST e os comandos de liberação enviados para a máquina.*
    
---
## 📂 Estrutura do Repositório

O repositório está organizado em módulos independentes, refletindo a arquitetura distribuída do projeto:

```text
/
│
├── software/                 # Sistema Web (Frontend e Backend)
│   ├── src/                  # Arquivos-fonte do e-commerce e API (PHP, HTML, etc.)
│   ├── dashborad.md          # Documentação do painel administrativo web
│   └── README.md             # Instruções específicas do módulo Web
│
├── hardware/                 # Firmware do microcontrolador (ESP32)
│   ├── src/                  # Código C++ (sketch.ino, config.h, qrcode.h)
│   ├── conexoes.md           # Mapeamento detalhado dos GPIOs e pinos
│   ├── manual-operador.md    # Guia de reabastecimento via controle remoto IR
│   └── README.md             # Instruções de compilação do ESP32
│
├── simulator/                # Digital Twin da Vending Machine
│   ├── img/                  # Assets gráficos para a interface visual
│   ├── vmachine.py           # Script principal (Python + Tkinter)
│   └── README.md             # Instruções de uso do simulador
│
├── brigde-paho/              # Gateway IoT 1 (MQTT para REST)
│   ├── src/
│   │   └── brigde_rest_mqtt.py # Script de roteamento de dados
│   └── README.md
│
└── paho-mqtt/                # Gateway IoT 2 (MQTT para Ubidots)
    ├── paho_mqtt_to_ubidots.py # Script de telemetria analítica
    └── README.md
```

## 🧩 Módulos do Projeto

Este repositório está subdividido em 4 módulos principais:

### 🌐 1. Sistema Web (Frontend e Backend REST)
Plataforma desenvolvida em **PHP e MySQL** responsável por toda a lógica comercial e de estoque da máquina.
* **Integração PIX:** Geração e validação automatizada de pagamentos.
* **Painel Admin:** Interface restrita para gerir pedidos e forçar liberações.
* **Conexão IoT:** Utiliza `cURL` para enviar requisições à API do ThingSpeak, repassando o ID do produto (`field1`) e o valor da compra (`field2`), com proteção anti-spam de 15 segundos para respeitar os limites do plano gratuito da API.

<img width="500" alt="Captura de tela de 2026-02-21 15-58-27" src="https://github.com/user-attachments/assets/c71fc4ca-6695-4487-aa46-fc869c6f6511" />
<img width="500" alt="Captura de tela de 2026-02-21 15-58-58" src="https://github.com/user-attachments/assets/c33ac96c-9223-4ecf-a134-531dcf45928e" />

### ⚙️ 2. Sistema Embarcado (Firmware ESP32)
O "cérebro" físico da máquina, programado em **C++**, construído para ser totalmente não-bloqueante.
* **Dual-Broker MQTT:** Conecta-se ao ThingSpeak (recebimento de comandos) e ao HiveMQ via SSL/TLS (envio de telemetria em formato JSON).
* **Controle de Hardware:** Gerencia os 16 pinos de saída para os servomotores/LEDs de produtos.
* **Sensores Ambientais:** Leitura contínua de Temperatura/Umidade (DHT22) e Presença física (PIR).
* **Interface Dinâmica:** Possui um display OLED (SSD1306) que alterna a cada 5 segundos entre telemetria de sensores e um QR Code estático (`PROGMEM`) para pagamentos.
* **Painel Administrativo Local:** Acesso via controle remoto Infravermelho (IR) com senha, permitindo auditar e reabastecer as quantidades do estoque físico.

<img width="600" alt="Captura de tela de 2026-02-21 15-59-50" src="https://github.com/user-attachments/assets/4aeba524-c9a6-48f2-9b7c-7e6487da97f3" />

### 🌉 3. Gateway IoT (Python MQTT Bridge)
Um script servidor feito em **Python (Paho-MQTT)** que atua como tradutor e roteador de dados.
* Assina os tópicos `#` (wildcard) da máquina no broker **HiveMQ**.
* Traduz os pacotes JSON brutos do ESP32.
* Roteia instantaneamente os dados de estoque e sensores para o **Ubidots** (via MQTT) e para o **ThingSpeak** (via API REST HTTP POST) para geração de gráficos analíticos em nuvem.

### 🖥️ 4. Simulador Digital Twin (Python Tkinter)
Aplicação visual em **Python com Tkinter** que funciona como um "Gêmeo Digital" da máquina física.
* Conecta-se diretamente ao broker MQTT do ThingSpeak.
* Representa uma matriz visual 4x4 com os 16 produtos.
* Permite validar o fluxo de rede do Backend Web sem a necessidade de ter o hardware ESP32 ligado, alterando a interface gráfica quando um produto é "liberado".

<img width="500" alt="Captura de tela de 2026-02-21 15-59-18" src="https://github.com/user-attachments/assets/e33d3965-112a-4495-90cc-f5be7cbddd87" />

---

## 🛠️ Tecnologias Utilizadas

**Linguagens de Programação:**
* `PHP 7.4+` (Backend Web)
* `C++` (Firmware ESP32)
* `Python 3` (Gateway e Simulador Tkinter)
* `HTML5 / CSS3 / JavaScript` (Frontend)

**Bancos de Dados e Nuvem:**
* `MySQL / MariaDB` (Persistência comercial)
* `ThingSpeak` (Broker MQTT para comandos / REST API para gráficos)
* `HiveMQ Cloud` (Broker MQTT seguro via porta 8883 para telemetria)
* `Ubidots` (Plataforma de visualização analítica IoT)

**Bibliotecas Principais:**
* *C++:* `PubSubClient`, `ArduinoJson`, `Adafruit GFX/SSD1306`, `DHT sensor library`, `IRremote`.
* *Python:* `paho-mqtt`, `requests`, `tkinter`.

---

## 🚀 Como Executar o Ecossistema

Para rodar este projeto em sua totalidade, você deve configurar cada módulo separadamente:

1. **Web:** Importe o banco de dados da pasta `/SQL`, configure o arquivo `app/config.php` (credenciais DB, PIX e Write Key do ThingSpeak) e suba em um servidor Apache/Nginx.
2. **ESP32:** Abra o código no Arduino IDE ou Wokwi, preencha as credenciais de Wi-Fi, ThingSpeak (MQTT) e HiveMQ (MQTT/SSL) no arquivo `config.h`, e faça o upload para a placa.
3. **Bridge Python:** Instale as dependências (`pip install paho-mqtt requests`), insira seus tokens do Ubidots e ThingSpeak, e rode o script `bridge.py` em uma máquina local ou servidor VPS 24/7.
4. **Simulador:** Execute o script do Tkinter no seu computador para visualizar a máquina operando digitalmente.

---

📄 **Licença e Autor**
Desenvolvido para revolucionar a forma como interagimos com as máquinas de vendas automáticas através da Internet das Coisas.

Dados extras: https://docs.google.com/document/d/138tYw3gOOYB4C3fEqaHNxyRHH1nxvoL85KSH4Srz9ds/edit?usp=sharing
