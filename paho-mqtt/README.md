# 📡 Envio de Dados Sintéticos ao Ubidots via MQTT com Python (paho-mqtt)

## Visão Geral

Este script tem como objetivo **simular um dispositivo IoT** enviando dados sintéticos (gerados artificialmente) para a plataforma **Ubidots** utilizando o protocolo **MQTT**.  

Ele funciona como um **publicador MQTT**, representando um sensor virtual que transmite continuamente medições ambientais para um device configurado na nuvem.

O uso desse tipo de script é muito comum em:

- testes de dashboards IoT
- validação de pipelines de dados
- simulação de sensores antes do hardware real existir
- desenvolvimento acadêmico em IoT
- criação de digital twins
- validação de integração cloud ↔ dispositivo

---

## 🌐 Comunicação com o Ubidots

A comunicação ocorre através do **broker MQTT oficial do Ubidots**, que recebe mensagens publicadas por dispositivos autenticados e as converte automaticamente em variáveis dentro da plataforma.

O fluxo geral é:

Dispositivo Python → Broker MQTT Ubidots → Device → Variáveis → Dashboard / API

O script estabelece uma conexão persistente com o servidor MQTT do Ubidots e envia dados periodicamente.

---

## 🔐 Dados de Conexão ao Ubidots

Para que a comunicação funcione corretamente, alguns parâmetros são essenciais:

### Broker MQTT
O endpoint utilizado pertence à infraestrutura industrial do Ubidots, responsável por receber mensagens IoT via MQTT.

Esse broker atua como intermediário entre o dispositivo e o armazenamento de dados na plataforma.

---

### Token de Autenticação
A autenticação não utiliza usuário e senha tradicionais.  
O Ubidots utiliza um **Token de acesso**, que funciona como uma chave de API.

Esse token:

- identifica a conta Ubidots
- autoriza o envio de dados
- vincula o dispositivo ao workspace correto
- substitui credenciais convencionais

O recomendado é utilizar o **Default Token** gerado pela própria plataforma.

---

### Device Label
O envio não é feito para um ID numérico, mas sim para o **Device Label**.

O Device Label:

- representa o nome lógico do dispositivo
- define automaticamente onde os dados serão armazenados
- permite criação dinâmica de variáveis

Se o device ainda não existir, o Ubidots pode criá-lo automaticamente ao receber dados.

---

## 📶 Uso do MQTT no Script

O protocolo MQTT é utilizado como meio principal de comunicação por ser:

- leve (ideal para IoT)
- baseado em publish/subscribe
- eficiente em redes instáveis
- adequado para transmissão contínua de sensores

Neste caso específico, o script atua como:

**Publisher MQTT (cliente publicador)**

Ele envia mensagens periodicamente para um tópico específico associado ao device.

Cada mensagem enviada representa um conjunto de medições simuladas.

---

## 📊 Dados Sintéticos Gerados

O script cria dados aleatórios para simular sensores reais, incluindo:

- temperatura
- umidade
- pressão atmosférica
- luminosidade

Esses valores são gerados automaticamente dentro de intervalos realistas, simulando leituras ambientais de um sistema embarcado.

Após o envio, o Ubidots interpreta cada chave do objeto enviado como uma variável independente do dispositivo.

Isso permite:

- geração automática de gráficos
- armazenamento histórico
- criação de alertas
- análise em tempo real

---

## 🔄 Funcionamento do Envio

Após conectar ao broker MQTT:

1. O cliente MQTT inicia uma conexão persistente.
2. O loop de rede roda em segundo plano.
3. Dados sintéticos são gerados periodicamente.
4. Os dados são convertidos para formato JSON.
5. A mensagem é publicada no tópico do dispositivo.
6. O Ubidots recebe e armazena os valores.
7. Dashboards são atualizados automaticamente.

Esse processo se repete continuamente enquanto o script estiver ativo.

---

## 🧠 Por que usar MQTT em vez de HTTP?

O MQTT é especialmente vantajoso em IoT porque:

- mantém conexão aberta (menos overhead)
- reduz consumo de banda
- permite envio frequente de dados
- funciona bem em dispositivos embarcados
- suporta milhares de dispositivos simultaneamente

Enquanto HTTP é orientado a requisições individuais, o MQTT é orientado a eventos contínuos.

---

## 🧰 Onde o paho-mqtt pode ser utilizado

A biblioteca **paho-mqtt** é uma das implementações MQTT mais utilizadas em Python e pode ser aplicada em diversos contextos:

### Sistemas IoT embarcados
Integração com ESP32, Raspberry Pi, gateways IoT e sensores industriais.

### Simulação de sensores
Testar plataformas cloud antes da implementação física.

### Digital Twin
Criar representações virtuais de dispositivos reais.

### Monitoramento remoto
Enviar telemetria de máquinas, servidores ou ambientes.

### Integração entre sistemas
Comunicação desacoplada entre aplicações distribuídas.

### Pesquisa acadêmica
Experimentos com protocolos IoT e arquiteturas distribuídas.

### Automação e Indústria 4.0
Envio contínuo de dados de produção e sensores industriais.

---

## 🔁 Casos de Uso Práticos

Este tipo de script pode ser usado para:

- validar dashboards Ubidots sem hardware
- testar limites de taxa de envio
- simular múltiplos dispositivos IoT
- demonstrar arquiteturas MQTT em aulas
- desenvolver backend antes do firmware
- integrar pipelines de dados IoT

---

## ⚠️ Boas Práticas

- Nunca compartilhar tokens reais em repositórios públicos.
- Utilizar variáveis de ambiente para armazenar credenciais.
- Controlar frequência de envio para evitar limites da plataforma.
- Encerrar corretamente conexões MQTT ao finalizar o programa.

---

## ✅ Resumo

O script representa um **sensor virtual IoT**, utilizando Python e paho-mqtt para publicar dados sintéticos no Ubidots através do protocolo MQTT.

Ele demonstra na prática:

- autenticação em plataforma IoT cloud
- envio contínuo de telemetria
- estrutura publish MQTT
- criação automática de variáveis
- integração simples entre software e dashboards IoT

Esse modelo é ideal como base para evoluir posteriormente para dispositivos reais como ESP32, Raspberry Pi ou gateways industriais.
