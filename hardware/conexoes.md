# 🔌 Diagrama de Hardware e Conexões - ESP32 Vending Machine

Este documento detalha o mapeamento de pinos (GPIOs) e as conexões físicas entre o microcontrolador ESP32 e os componentes da Vending Machine Fibrag.

O projeto foi otimizado para garantir estabilidade elétrica, respeitando os pinos de *boot-strapping* (como a troca estratégica do GPIO 0) e pinos exclusivos de entrada (como GPIO 34 e 35).

---

## 🖥️ 1. Display e Comunicação (I2C)

O display OLED utiliza o barramento I2C padrão do ESP32.

| Componente | Pino do Componente | Pino ESP32 (GPIO) | Tipo | Observações |
| :--- | :--- | :--- | :--- | :--- |
| **Display OLED SSD1306** | VCC | 3.3V | Alimentação | Usar a saída 3.3V do ESP32. |
| | GND | GND | Terra | Conectar ao GND comum. |
| | SDA | **GPIO 21** | I2C Data | Pino padrão I2C do ESP32. |
| | SCL | **GPIO 22** | I2C Clock | Pino padrão I2C do ESP32. |

---

## 📡 2. Sensores e Interface de Usuário

Conexões para telemetria (temperatura, umidade, presença), controle remoto e feedback sonoro.

| Componente | Pino do Componente | Pino ESP32 (GPIO) | Tipo | Observações |
| :--- | :--- | :--- | :--- | :--- |
| **Sensor DHT22** | VCC | 3.3V / 5V | Alimentação | |
| | GND | GND | Terra | |
| | DATA (Out) | **GPIO 27** | Entrada Digital |  |
| **Sensor PIR** | VCC | 5V | Alimentação | Geralmente requer 5V (pino VIN do ESP32). |
| | GND | GND | Terra | |
| | OUT | **GPIO 34** | Entrada Digital | Pino apenas de entrada (Input-only), perfeito para o PIR. |
| **Receptor IR** | VCC | 3.3V | Alimentação | |
| | GND | GND | Terra | |
| | OUT | **GPIO 35** | Entrada Digital | Pino apenas de entrada (Input-only). |
| **Buzzer Ativo** | VCC / + | **GPIO 2** | Saída Digital | Emite os bipes de sucesso, erro e navegação IR. |
| | GND / - | GND | Terra | |

---

## 💡 3. Atuadores (LEDs / Servomotores)

Esta tabela mapeia os 16 pinos de saída responsáveis por liberar os produtos da máquina (simulados por LEDs no simulador).

⚠️ **Importante:** Todos os componentes desta lista são configurados como **Saída Digital (OUTPUT)**. Se você substituir os LEDs por Servomotores ou Relés no projeto físico, mantenha este exato mapeamento.

| ID do Produto | Nome do Produto | Pino ESP32 (GPIO) | Status de Conexão |
| :---: | :--- | :---: | :--- |
| **1** | Batata | **GPIO 13** | OK |
| **2** | Doritos | **GPIO 12** | OK |
| **3** | Chocolate | **GPIO 14** | OK |
| **4** | Biscoito | **GPIO 0** | OK  |
| **5** | Amendoim | **GPIO 26** | OK |
| **6** | Goma | **GPIO 25** | OK |
| **7** | Refrigerante | **GPIO 33** | OK |
| **8** | Suco | **GPIO 32** | OK |
| **9** | Água | **GPIO 15** | OK |
| **10** | Barra de Cereal | **GPIO 18** | OK |
| **11** | Pipoca | **GPIO 5** | OK |
| **12** | Bala | **GPIO 17** | OK |
| **13** | Cookies | **GPIO 16** | OK |
| **14** | Snack | **GPIO 19** | OK |
| **15** | Torrada | **GPIO 23** | OK |
| **16** | Bombom | **GPIO 4** | OK |

---

## ⚡ 4. Recomendações de Alimentação (Hardware Físico)

Caso você monte este projeto fora do simulador Wokwi (com componentes reais):
1. **Alimentação Separada:** O ESP32 **não consegue** fornecer corrente suficiente (mA) para alimentar 16 servomotores físicos simultaneamente.
2. **Fonte Externa:** Utilize uma fonte de bancada ou fonte chaveada externa de 5V (com amperagem adequada, ex: 5A ou mais) para alimentar os Servos/Relés.
3. **GND Comum:** É estritamente necessário interligar o cabo terra (GND) da fonte externa com um dos pinos GND do ESP32 para que os sinais lógicos funcionem corretamente.
