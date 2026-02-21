# 🛒 Fibrag Vending Machine - ESP32 IoT Controller

Este projeto implementa o firmware de controle para uma Vending Machine inteligente baseada no microcontrolador ESP32. O sistema é totalmente não-bloqueante, apresenta uma interface OLED dinâmica (incluindo QR Code para pagamentos via PIX/Gepix) e comunica-se simultaneamente com dois brokers MQTT para telemetria e controle de estoque.

## ✨ Funcionalidades

* **Arquitetura Dual-MQTT:** Recebe comandos de liberação via ThingSpeak e publica dados de vendas e telemetria em tempo real via HiveMQ (SSL).
* **Telemetria de Sensores:** Monitoramento contínuo de temperatura e umidade (DHT22) e detecção instantânea de presença física (PIR).
* **Display OLED Dinâmico:** Alternância de tela a cada 5 segundos entre uma interface de status (texto rolante + sensores) e um QR Code para pagamento.
* **Menu Administrativo IR:** Controle local via controle remoto infravermelho com autenticação por senha para visualização e edição de estoque.
* **Sistema Não-Bloqueante:** Uso intensivo de `millis()` para garantir que a leitura de sensores, a animação da tela e a comunicação Wi-Fi funcionem em paralelo e sem travamentos.

## 🧰 Hardware e Pinout

O projeto requer os seguintes componentes:
* 1x ESP32
* 1x Display OLED I2C SSD1306 (128x64)
* 1x Sensor de Temperatura/Umidade DHT22
* 1x Sensor de Movimento PIR
* 1x Receptor IR
* 1x Buzzer Ativo
* 16x LEDs (simulando a liberação dos servomotores dos produtos)

### Mapeamento de Pinos (GPIO)
* **Sensores e Atuadores Base:**
  * `SDA / SCL` -> Pinos I2C Padrão (21, 22) para Display OLED
  * `DHT_PIN` -> GPIO 27 *(Nota: Movido do pino 0 para o 27 para evitar conflitos no boot-strapping).*
  * `PIR_PIN` -> GPIO 34
  * `IR_PIN` -> GPIO 35
  * `BUZZER_PIN` -> GPIO 2
* **Matriz de Produtos (LEDs/Servos):**
  * Prods 1 a 8: `13, 12, 14, 0, 26, 25, 33, 32`
  * Prods 9 a 16: `15, 18, 5, 17, 16, 19, 23, 4`

## 📚 Dependências (Bibliotecas)

Certifique-se de instalar as seguintes bibliotecas na IDE do Arduino ou listar no `libraries.txt` do Wokwi:
* `WiFi` e `WiFiClientSecure` (Nativas do ESP32)
* `PubSubClient` (Para MQTT)
* `ArduinoJson` (Para formatação de dados de envio)
* `Adafruit GFX Library` e `Adafruit SSD1306` (Para o display)
* `DHT sensor library` e `Adafruit Unified Sensor` (Para temperatura/umidade)
* `IRremote` (Para o controle remoto)

## 📂 Estrutura do Projeto

O código-fonte foi modularizado para facilitar a manutenção:

* `sketch.ino` (ou `main.cpp`): Arquivo principal contendo as máquinas de estado, setup e loop não-bloqueante.
* `config.h`: Credenciais Wi-Fi, configurações de ambos os brokers MQTT, mapeamento de pinos e configuração inicial dos produtos.
* `qrcode.h`: Matriz hexadecimal (`PROGMEM`) isolada contendo o design 64x64 do QR Code da plataforma.

## 📡 Protocolo de Comunicação MQTT

A máquina publica dados em formato JSON para o **HiveMQ**:

### 1. Tópico de Status (`vending/machine/status`)
Enviado periodicamente (a cada 30 segundos) ou forçado instantaneamente se o sensor PIR detectar movimento.
```json
{
  "id": 0,
  "temp": 24.5,
  "hum": 60.2,
  "pir": 1
}
```

(O id: 0 indica que é apenas um heartbeat/status, sem venda).

### 2. Tópico de Vendas (vending/machine/vendas)
Enviado IMEDIATAMENTE após um produto ser liberado com sucesso.

```
{
  "id": 4,
  "estoque_atual": 8,
  "temp": 24.5,
  "hum": 60.2,
  "pir": 1
}
```
## 3. Recebimento de Comandos (ThingSpeak)
O ESP32 está inscrito no tópico channels/<CHANNEL_ID>/subscribe/fields/field1. Ao receber um payload numérico (ex: "4"), a máquina aciona a rotina girarServoDireto() para liberar o produto correspondente.

### ⚙️ Uso do Menu Administrativo (IR)
Para acessar o painel de administração local na máquina:

- Pressione a tecla POWER (P) no controle remoto.

- Digite a senha padrão: 1234 seguida de PLAY (#).

Navegue no menu:

   - Opção 1: Visualizar estoque atual (Use PREV e NEXT para navegar).

   - Opção 2: Editar quantidade de estoque de um produto específico.

### 🚀 Como Executar
- Clone este repositório.

- Abra os arquivos na IDE do Arduino ou no simulador Wokwi.

- No arquivo config.h, preencha os dados do seu Wi-Fi (ssid e password) e as credenciais reais do ThingSpeak e HiveMQ.

- Faça o upload para a placa ESP32.

